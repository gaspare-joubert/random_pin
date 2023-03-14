<?php

namespace GaspareJoubert\RandomPin;

use GaspareJoubert\RandomPin\Models\RandomPins;
use Generator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Translation\Exception\LogicException;

class RandomPinFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'random_pin';
    }

    /**
     * Get available, stored PINs.
     * Generate and store PINs if none are available.
     *
     * @param int $limit The maximum number of recursive calls allowed is 2.
     * @return array $randomPinsToEmit, an array of random PINs.
     */
    public static function getPIN(int $limit = 1): array
    {
        $randomPinsToEmit = [];

        if ($limit <= 2) {
            $permittedCharacters = config('random_pin.permitted_characters') ?? '';
            $numberOfPINsToGet = config('random_pin.number_of_pins_to_get') ?? '';

            if (!$permittedCharacters || !$numberOfPINsToGet) {
                Log::error('Unable to get pin. Missing Permitted Characters or Number of pins to get.');
                return $randomPinsToEmit;
            }

            // we first check if any pins have been generated using the permitted characters
            try {
                $pinsByPermittedCharacters = RandomPins::withoutTrashed()
                    ->where('permitted_characters', $permittedCharacters)
                    ->limit(1)
                    ->get(['uuid']);

                if ($pinsByPermittedCharacters->count() == 0) {
                    // go ahead and generate pins
                    if (self::generatePINs($permittedCharacters) === true) {
                        $limit++;
                        self::getPIN($limit);
                    }
                    // generating the pins has failed
                    return $randomPinsToEmit;
                }
            } catch (\Exception $ex) {
                Log::debug("Unable to get pins by permitted characters: {$ex->getMessage()}");
                return $randomPinsToEmit;
            }

            // next we check if any pins using the permitted characters are still available
            try {
                $randomPins = RandomPins::withoutTrashed()
                    ->where('permitted_characters', $permittedCharacters)
                    ->where('has_been_emitted', 0)
                    ->inRandomOrder()
                    ->limit($numberOfPINsToGet)
                    ->get(['uuid','pin']);

                $randomPinsCount = $randomPins->count();

                if ($randomPinsCount == $numberOfPINsToGet) {
                    foreach ($randomPins as $randomPin) {
                        // update the pin has been emitted
                        try {
                            RandomPins::where('uuid', $randomPin->uuid)
                                ->update(['has_been_emitted' => 1]);

                            $randomPinsToEmit[] = $randomPin->pin;
                        } catch (\Exception $ex) {
                            Log::debug("Unable to update random pins when count is equal: {$ex->getMessage()}");
                            return $randomPinsToEmit;
                        }
                    }

                    return $randomPinsToEmit;
                }

                if ($randomPinsCount == 0 || ($randomPinsCount > 0 && $randomPinsCount < $numberOfPINsToGet)) {
                    // if we do not have enough pins left to emit
                    // reset all the ones which have not been deleted
                    // get the number of required pins
                    try {
                        RandomPins::withoutTrashed()
                            ->where('permitted_characters', $permittedCharacters)
                            ->update(['has_been_emitted' => 0]);

                        $limit++;
                        self::getPIN($limit);
                    } catch (\Exception $ex) {
                        Log::debug("Unable to update random pins when count is not equal: {$ex->getMessage()}");
                        return $randomPinsToEmit;
                    }
                }
            } catch (\Exception $ex) {
                Log::debug("Unable to get random pins: {$ex->getMessage()}");
                return $randomPinsToEmit;
            }
        } else {
            Log::debug('Unable to get random pins with the maximum number of calls.');
            return $randomPinsToEmit;
        }

        return $randomPinsToEmit;
    }

    /**
     * @param string $permittedCharacters
     * @return bool
     */
    private static function generatePINs (string $permittedCharacters): bool
    {
        $permittedCharactersIsValid = true;
        $maxLength = 8;
        $minLength = 2;
        $testResultMessages = [];

        if (strlen($permittedCharacters) > $maxLength) {
            $permittedCharactersIsValid = false;
            $testResultMessages[] = "Maximum length of {$maxLength} characters exceeded.";
        }

        if (strlen($permittedCharacters) < $minLength) {
            $permittedCharactersIsValid = false;
            $testResultMessages[] = "Minimum length of characters is {$minLength} .";
        }

        // only numbers are permitted
        if (preg_match('/^\d+$/', $permittedCharacters) !== 1) {
            $permittedCharactersIsValid = false;
            $testResultMessages[] = 'Only numbers are allowed.';
        }

        if ($permittedCharactersIsValid) {
            return self::generateNumericalPIN($permittedCharacters);
        } else {
            $testResultMessages = implode(' ', $testResultMessages);
            Log::debug("Permitted characters are not valid: {$testResultMessages}");
            return false;
        }
    }

    /**
     * @param string $permittedCharacters
     * @return bool
     */
    private static function generateNumericalPIN(string $permittedCharacters): bool
    {
        $permittedCharactersArray = str_split($permittedCharacters, 1);
        $permittedCharactersArrayMin = [];
        $permittedCharactersArrayMax = [];
        foreach ($permittedCharactersArray as $item)
        {
            $permittedCharactersArrayMin[] = '1';
            $permittedCharactersArrayMax[] = '9';
        }

        $permittedCharactersMin = implode($permittedCharactersArrayMin);
        $permittedCharactersMax = implode($permittedCharactersArrayMax);

        try {
            foreach (self::xRange($permittedCharactersMin, $permittedCharactersMax, 1) as $pin) {
                try {
                    $validatePIN = new ValidatePIN(new SetupPIN(), $pin);

                    if ($validatePIN->validatePin() === 'pass') {
                        try {
                            $randomPins = new RandomPins();
                            $randomPins->uuid = Uuid::uuid4();
                            $randomPins->pin = $pin;
                            $randomPins->permitted_characters = $permittedCharacters;
                            $randomPins->save();
                        } catch (\Exception $ex) {
                            Log::debug("Unable to store validated pin '{$pin}': {$ex->getMessage()}");
                            return false;
                        }
                    }
                } catch (\Exception $ex) {
                    Log::debug("Unable to instantiate ValidatePIN: {$ex->getMessage()}");
                }
            }

            return true;
        } catch (\Exception $ex) {
            Log::debug("Unable to validate pins: {$ex->getMessage()}");
            return false;
        }
    }

    /**
     * A Generator equivalent of the range function.
     *
     * @param $start
     * @param $limit
     * @param int $step
     * @return Generator
     */
    private static function xRange($start, $limit, int $step = 1): Generator
    {
        if ($start <= $limit) {
            if ($step <= 0) {
                throw new LogicException('Step must be positive');
            }

            for ($i = $start; $i <= $limit; $i += $step) {
                yield $i;
            }
        } else {
            if ($step >= 0) {
                throw new LogicException('Step must be negative');
            }

            for ($i = $start; $i >= $limit; $i += $step) {
                yield $i;
            }
        }
    }
}
