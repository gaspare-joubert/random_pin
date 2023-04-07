<?php

namespace GaspareJoubert\RandomPin;

use GaspareJoubert\RandomPin\Models\RandomPin;
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
    public static function getPin(int $limit = 1): array
    {
        $randomPinsToEmit = [];

        if ($limit <= 2) {
            $countApplicationParameters = self::getApplicationParameters();

            if (!($countApplicationParameters > 0)) {
                Log::error('Unable to get pin. Application parameters could not be found.');
                return [];
            }


        } else {
            Log::debug('Unable to get random pins within the maximum number of allowed calls.');
            return [];
        }

        return $randomPinsToEmit;
    }

    /**
     * Get the parameters required in order for the application to continue operation.
     *
     * @param string $requiredApplicationParametersKey The config key for the application's required parameters.
     * @return array
     */
    public static function getApplicationParameters(string $requiredApplicationParametersKey = 'required_application_parameters'): array
    {
        $applicationParameters = [];
        $facadeAccessor = self::getFacadeAccessor() ?? '';
        $requiredApplicationParameters = config($facadeAccessor . '.' . $requiredApplicationParametersKey) ?? false;

        if ($requiredApplicationParameters) {
            foreach ($requiredApplicationParameters as $key => $parameter) {
                if ($config = config($facadeAccessor . '.' . $requiredApplicationParametersKey . '.' . $key)) {
                    $applicationParameters[$key] = $config;
                } else {
                    Log::error("Unable to get the parameter '{$key}'.");
                    return [];
                }
            }
        } else {
            Log::error("Unable to get the application's required parameters.");
            return [];
        }

        return $applicationParameters;
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
            foreach (self::xRange($permittedCharactersMin, $permittedCharactersMax, 1) as $generatedPin) {
                try {
                    $pin = self::$app->make(PIN::class, ['pin' => $generatedPin]);

                    if (self::validatePin($pin)) {
                        try {
                            $randomPin = new RandomPin();
                            $randomPin->uuid = Uuid::uuid4();
                            $randomPin->pin = $generatedPin;
                            $randomPin->permitted_characters = $permittedCharacters;
                            $randomPin->save();
                        } catch (\Exception $ex) {
                            Log::debug("Unable to store validated pin '{$generatedPin}': {$ex->getMessage()}");
                            return false;
                        }
                    }
                } catch (\Exception $ex) {
                    Log::debug("Unable to instantiate PIN: {$ex->getMessage()}");
                }
            }

            return true;
        } catch (\Exception $ex) {
            Log::debug("Unable to validate pins: {$ex->getMessage()}");
            return false;
        }
    }

    /**
     * Get the minimum and maximum range of a numerical PIN.
     * Based on the length of the PIN.
     *
     * @param int $pinLength
     * @return array
     */
    public static function getNumericalPinRange(int $pinLength): array
    {
        $numericalPinRange['Min'] = str_repeat('0', $pinLength);
        $numericalPinRange['Max'] = str_repeat('9', $pinLength);

        return $numericalPinRange;
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
     * If any of the test conditions returns true, the pin has failed validation.
     *
     * @param PIN $pin
     * @return bool
     */
    private static function validatePin(PIN $pin): bool
    {
        $tests = get_object_vars($pin) ?: '';

        foreach ($tests as $key => $value) {
            if ($value === true) {
                return false;
            }
        }

        return true;
    }
}
