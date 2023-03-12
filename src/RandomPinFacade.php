<?php

namespace GaspareJoubert\RandomPin;

use GaspareJoubert\RandomPin\Models\RandomPins;
use Illuminate\Support\Facades\Facade;

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

    // todo: limit number of times recursive call can be made
    public static function getPIN()
    {
        $randomPinsToEmit = [];

        $permittedChars = config('random_pin.permitted_characters') ?? '';
        $numberOfPINsToGet = config('random_pin.number_of_pins_to_get') ?? '';

        if (!$permittedChars || !$numberOfPINsToGet) {
            return;
        }

        // we first check if any pins have been generated using the permitted characters
        $pinsByPermittedCharacters = RandomPins::withoutTrashed()
            ->where('permitted_characters', $permittedChars)
            ->limit(1)
            ->get(['uuid']);

        if ($pinsByPermittedCharacters->count() == 0) {
            // go ahead and generate pins
            // todo: generate pins
            self::getPIN();
        }

        // next we check if any pins using the permitted characters are still available
        $randomPins = RandomPins::withoutTrashed()
            ->where('permitted_characters', $permittedChars)
            ->where('has_been_emitted', 0)
            ->inRandomOrder()
            ->limit($numberOfPINsToGet)
            ->get(['uuid','pin']);

        $randomPinsCount = $randomPins->count();

        if ($randomPinsCount == $numberOfPINsToGet) {
            foreach ($randomPins as $randomPin) {
                $randomPinsToEmit[] = $randomPin->pin;

                // update the pin has been emitted
                RandomPins::where('uuid', $randomPin->uuid)
                    ->update(['has_been_emitted' => 1]);
            }

            return $randomPinsToEmit;
        }

        if ($randomPinsCount == 0 || ($randomPinsCount > 0 && $randomPinsCount < $numberOfPINsToGet)) {
            // if we do not have enough pins left to emit
            // reset all the ones which have not been deleted
            // get the number of required pins
            RandomPins::where('permitted_characters', $permittedChars)
                ->where('deleted_at', null)
                ->update(['has_been_emitted' => 0]);

            self::getPIN();
        }
    }

    // todo: complete generating the PINs
    private function generatePINs ($permittedChars)
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
