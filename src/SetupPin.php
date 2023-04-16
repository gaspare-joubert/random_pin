<?php

namespace GaspareJoubert\RandomPin;

class SetupPin implements iPin
{
    /**
     * @inheritDoc
     */
    public function isPalindrome(string $pin): bool
    {
        return $pin === strrev($pin);
    }

    /**
     * @inheritDoc
     */
    public function isSequential(string $pin): bool
    {
        $pinArray = str_split($pin, 1);
        $countPinArray = count($pinArray);
        $differenceTotal = 0;
        $differenceOfOneOccurrences = 0;
        for ($i = 0; $i < $countPinArray - 1; $i++) {
            $difference = abs($pinArray[$i] - $pinArray[$i + 1]);
            $differenceTotal += $difference;
            if ($difference == 1) {
                $differenceOfOneOccurrences++;
            }
        }

        if (($differenceTotal == $countPinArray - 1) || ($differenceOfOneOccurrences == $countPinArray - 2)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isRepeating(string $pin): bool
    {
        if (strlen($pin) <= 4) {
            return count(array_filter(count_chars($pin, 1), function ($value) {
                    return $value > 1;
                })) > 0;
        }

        return false;
    }
}
