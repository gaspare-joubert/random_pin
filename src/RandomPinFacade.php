<?php

namespace GaspareJoubert\RandomPin;

use GaspareJoubert\RandomPin\Models\RandomPins;
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
                            ->where('deleted_at', null)
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
}
