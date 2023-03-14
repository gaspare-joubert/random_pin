<?php

namespace GaspareJoubert\RandomPin;

use GaspareJoubert\RandomPin\Models\RandomPins;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;

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
            $permittedChars = config('random_pin.permitted_characters') ?? '';
            $numberOfPINsToGet = config('random_pin.number_of_pins_to_get') ?? '';

            if (!$permittedChars || !$numberOfPINsToGet) {
                Log::error('Unable to get pin. Missing Permitted Characters or Number of pins to get.');
                return $randomPinsToEmit;
            }

            // we first check if any pins have been generated using the permitted characters
            try {
                $pinsByPermittedCharacters = RandomPins::withoutTrashed()
                    ->where('permitted_characters', $permittedChars)
                    ->limit(1)
                    ->get(['uuid']);

                if ($pinsByPermittedCharacters->count() == 0) {
                    // go ahead and generate pins
                    // todo: generate pins
                    $limit++;
                    self::getPIN($limit);
                }
            } catch (\Exception $ex) {
                Log::debug("Unable to get pins by permitted characters: {$ex->getMessage()}");
                return $randomPinsToEmit;
            }

            // next we check if any pins using the permitted characters are still available
            try {
                $randomPins = RandomPins::withoutTrashed()
                    ->where('permitted_characters', $permittedChars)
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
                            ->where('permitted_characters', $permittedChars)
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

    // todo: complete generating the PINs
    private function generatePINs (string $permittedChars)
    {
        $permittedCharactersIsValid = true;

        // confirm if the permitted characters are valid
        if ($permittedChars) {
            // this is the maximum schema length
            if (strlen($permittedChars) > 36) {
                $permittedCharactersIsValid = false;
            }
        }

        if ($permittedCharactersIsValid) {
            if (preg_match('/^\d+$/', $permittedChars) === 1) {
                // permitted characters are numerical
            } else {
                // permitted characters are alphanumerical
            };
        }
    }
}
