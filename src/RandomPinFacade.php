<?php

namespace GaspareJoubert\RandomPin;

use GaspareJoubert\RandomPin\Models\RandomPin;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
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
     * Get available, stored pins.
     * Generate and store pins if none are available.
     *
     * @param int $limit The maximum number of recursive calls allowed is 2.
     * @return array $randomPinsToEmit, an array of random pins.
     */
    public static function getPin(int $limit = 1): array
    {
        if ($limit <= 2) {
            $applicationParameters = self::getApplicationParameters();

            if (!(count($applicationParameters) > 0) || !(self::isApplicationParametersValid())) {
                Log::error('Unable to get pins using application parameters.');
                return [];
            }

            try {
                $randomPins = self::getRandomPins(self::getPinType($applicationParameters['permitted_characters']), $applicationParameters['number_of_pins_to_get']);

                if (count($randomPins) < $applicationParameters['number_of_pins_to_get']) {
                    if (self::generatePins(self::getPinType($applicationParameters['permitted_characters']), $applicationParameters['pin_length']['length']) === true) {
                        $limit++;
                        self::getPIN($limit);
                    }

                    return [];
                } else {
                    if (self::updateRandomPinHasBeenEmitted($randomPins->pluck('id')->all()) !== 'false') {
                        return $randomPins->pluck('pin')->all();
                    } else {
                        return [];
                    }
                }
            } catch (\Exception $ex) {
                Log::debug("Unable to get random pins: {$ex->getMessage()}");
                return [];
            }

        } else {
            Log::debug('Unable to get random pins within the maximum number of allowed calls.');
            return [];
        }
    }

    /**
     * @param array $pinIds
     * @return bool|int|string
     */
    private static function updateRandomPinHasBeenEmitted(array $pinIds)
    {
        try {
            return RandomPin::withoutTrashed()
                ->whereIn('id', $pinIds)
                ->update(['has_been_emitted' => 1]);
        } catch (\Exception $ex) {
            Log::debug("Unable to update 'has_been_emitted' to 1: {$ex->getMessage()}");
            return 'false';
        }
    }

    /**
     * @param int $pinType
     * @param int $limit
     * @return Collection
     */
    public static function getRandomPins(int $pinType, int $limit): Collection
    {
        try {
            return RandomPin::withoutTrashed()
                ->where('type', $pinType)
                ->where('has_been_emitted', 0)
                ->inRandomOrder()
                ->limit($limit)
                ->get(['id', 'pin']);
        } catch (\Exception $ex) {
            Log::debug("Unable to get random pins: {$ex->getMessage()}");
            return (collect());
        }
    }

    /**
     * Get the type of pin to generate, based on the config permitted_characters.
     *
     * @param string $permittedCharacters
     * @return int
     */
    public static function getPinType(string $permittedCharacters): int
    {
        return preg_match('/^\d+$/', $permittedCharacters) === 1 ? RandomPin::TYPE_NUMERICAL : RandomPin::TYPE_ALPHANUMERICAL;
    }

    /**
     * @param int $pinType
     * @param int $pinLength
     * @return bool
     */
    public static function generatePins(int $pinType, int $pinLength): bool
    {
        if ($pinType === RandomPin::TYPE_NUMERICAL) {
            if (self::deleteNumericalPins($pinType, $pinLength) !== 'false') {
                return self::generateNumericalPin(self::getNumericalPinRange($pinLength), $pinLength);
            } else {
                return false;
            }
        } else {
            Log::debug('Unable to generate alphanumerical pins.');
            return false;
        }
    }

    /**
     * Delete all numerical pins with the same number of digits as the config pin length.
     *
     * @param int $pinType
     * @param int $pinLength
     * @return int|string
     */
    public static function deleteNumericalPins(int $pinType, int $pinLength)
    {
        try {
            return RandomPin::withoutTrashed()
                ->where('type', $pinType)
                ->whereRaw("LENGTH(pin) = {$pinLength}")
                ->update(['deleted_at' => now()]);
        } catch (\Exception $ex) {
            Log::debug("Unable to delete {$pinLength} digit numerical pins: {$ex->getMessage()}");
            return 'false';
        }
    }

    /**
     * Use config application_parameter_conditions to test application parameters.
     * If at least one condition fails, return false.
     * If no conditions are defined, return true as default.
     *
     * @param string $applicationParameterConditionsKey
     * @return bool
     */
    public static function isApplicationParametersValid(string $applicationParameterConditionsKey = 'application_parameter_conditions'): bool
    {
        $facadeAccessor = self::getFacadeAccessor() ?? '';
        $applicationParameterConditions = config($facadeAccessor . '.' . $applicationParameterConditionsKey) ?? false;

        if ($applicationParameterConditions) {
            foreach ($applicationParameterConditions as $key => $applicationParameterCondition) {
                $collection = collect([['value' => (int)config($facadeAccessor . '.' . $applicationParameterCondition['statement']) ?? false]]);
                if (!($collection->where('value', $applicationParameterCondition['operator'], (int)config($facadeAccessor . '.' . $applicationParameterCondition['argument']))->isNotEmpty())) {
                    Log::debug("Application parameter condition failed: {$key}");
                    return false;
                }
            }
        }

        return true;
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
     * @param array $numericalPinRange
     * @param int $pinLength
     * @return bool
     */
    public static function generateNumericalPin(array $numericalPinRange, int $pinLength): bool
    {
        $data = [];
        switch ($pinLength) {
            case 5:
                $chunk = 10000;
                break;
            case 6:
            case 7:
            case 8:
                $chunk = 20000;
                break;
            default:
                $chunk = 1000;
                break;
        }

        try {
            foreach (self::xRange($numericalPinRange['Min'], $numericalPinRange['Max'], 1) as $generatedPin) {
                try {
                    $pin = self::$app->make(PIN::class, ['pin' => $generatedPin]);

                    if (self::validatePin($pin)) {
                        $data[] = [
                            'pin' => sprintf("%'.0{$pinLength}d", $generatedPin),
                            'type' => RandomPin::TYPE_NUMERICAL,
                        ];
                    }

                    if (count($data) === $chunk) {
                        try {
                            DB::table('random_pins')->insert($data);
                            $data = [];
                        } catch (\Exception $ex) {
                            Log::debug("Unable to insert {$chunk} pins: {$ex->getMessage()}");
                            return false;
                        }
                    }
                } catch (\Exception $ex) {
                    Log::debug("Unable to instantiate PIN: {$ex->getMessage()}");
                    return false;
                }
            }
            $countData = count($data);
            if ($countData > 0) {
                try {
                    DB::table('random_pins')->insert($data);
                } catch (\Exception $ex) {
                    Log::debug("Unable to insert {$countData} pins: {$ex->getMessage()}");
                    return false;
                }
            }

            return true;
        } catch (\Exception $ex) {
            Log::debug("Unable to validate pins: {$ex->getMessage()}");
            return false;
        }
    }

    /**
     * Get the minimum and maximum range of a numerical pin.
     * Based on the length of the pin.
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
     * This was taken from here: https://www.php.net/manual/en/language.generators.overview.php
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
    public static function validatePin(PIN $pin): bool
    {
        $tests = get_object_vars($pin) ?? [];

        foreach ($tests as $key => $test) {
            if ($test === true) {
                return false;
            }
        }

        return true;
    }
}
