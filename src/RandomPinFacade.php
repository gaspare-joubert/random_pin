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
        $randomPINsToEmit = [];

        if ($limit <= 2) {
            $permittedCharacters = config('random_pin.permitted_characters') ?? '';
            $numberOfPINsToGet = config('random_pin.number_of_pins_to_get') ?? '';

            if (!$permittedCharacters || !$numberOfPINsToGet) {
                Log::error('Unable to get PIN. Missing Permitted Characters or Number of PINs to get.');
                return $randomPINsToEmit;
            }

            // we first check if any PINs have been generated using the permitted characters
            try {
                $pINsByPermittedCharacters = RandomPins::withoutTrashed()
                    ->where('permitted_characters', $permittedCharacters)
                    ->limit(1)
                    ->get(['uuid']);

                if ($pINsByPermittedCharacters->count() == 0) {
                    // go ahead and generate PINs
                    if (self::generatePINs($permittedCharacters) === true) {
                        $limit++;
                        self::getPIN($limit);
                    }
                    // generating the PINs has failed
                    return $randomPINsToEmit;
                }
            } catch (\Exception $ex) {
                Log::debug("Unable to get PINs by permitted characters: {$ex->getMessage()}");
                return $randomPINsToEmit;
            }

            // next we check if any PINs using the permitted characters are still available
            try {
                $randomPINs = RandomPins::withoutTrashed()
                    ->where('permitted_characters', $permittedCharacters)
                    ->where('has_been_emitted', 0)
                    ->inRandomOrder()
                    ->limit($numberOfPINsToGet)
                    ->get(['uuid','pin']);

                $randomPINsCount = $randomPINs->count();

                if ($randomPINsCount == $numberOfPINsToGet) {
                    foreach ($randomPINs as $randomPIN) {
                        // update the PIN has been emitted
                        try {
                            RandomPins::where('uuid', $randomPIN->uuid)
                                ->update(['has_been_emitted' => 1]);

                            $randomPINsToEmit[] = $randomPIN->pin;
                        } catch (\Exception $ex) {
                            Log::debug("Unable to update random PINs when count is equal: {$ex->getMessage()}");
                            return $randomPINsToEmit;
                        }
                    }

                    return $randomPINsToEmit;
                }

                if ($randomPINsCount == 0 || ($randomPINsCount > 0 && $randomPINsCount < $numberOfPINsToGet)) {
                    // if we do not have enough PINs left to emit
                    // reset all the ones which have not been deleted
                    // get the number of required PINs
                    try {
                        RandomPins::withoutTrashed()
                            ->where('permitted_characters', $permittedCharacters)
                            ->update(['has_been_emitted' => 0]);

                        $limit++;
                        self::getPIN($limit);
                    } catch (\Exception $ex) {
                        Log::debug("Unable to update random PINs when count is not equal: {$ex->getMessage()}");
                        return $randomPINsToEmit;
                    }
                }
            } catch (\Exception $ex) {
                Log::debug("Unable to get random PINs: {$ex->getMessage()}");
                return $randomPINsToEmit;
            }
        } else {
            Log::debug('Unable to get random PINs with the maximum number of calls.');
            return $randomPINsToEmit;
        }

        return $randomPINsToEmit;
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
            foreach (self::xRange($permittedCharactersMin, $permittedCharactersMax, 1) as $generatedPIN) {
                try {
                    $pIN = new PIN(new SetupPIN(), $generatedPIN);

                    if (self::validatePIN($pIN) === 'pass') {
                        try {
                            $randomPINs = new RandomPins();
                            $randomPINs->uuid = Uuid::uuid4();
                            $randomPINs->pin = $generatedPIN;
                            $randomPINs->permitted_characters = $permittedCharacters;
                            $randomPINs->save();
                        } catch (\Exception $ex) {
                            Log::debug("Unable to store validated PIN '{$generatedPIN}': {$ex->getMessage()}");
                            return false;
                        }
                    }
                } catch (\Exception $ex) {
                    Log::debug("Unable to instantiate ValidatePIN: {$ex->getMessage()}");
                }
            }

            return true;
        } catch (\Exception $ex) {
            Log::debug("Unable to validate PINs: {$ex->getMessage()}");
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

    /**
     * If any of the test conditions returns true, the PIN has failed validation.
     * Return 'fail'.
     *
     * @param PIN $pIN
     * @return string
     */
    private static function validatePIN(PIN $pIN): string
    {
        $tests = get_object_vars($pIN) ?: '';

        if (!$tests) {
            return 'fail';
        }

        foreach ($tests as $key => $value) {
            if ($value === true) {
                return 'fail';
            }
        }

        return 'pass';
    }
}
