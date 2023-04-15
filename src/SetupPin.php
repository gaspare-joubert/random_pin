<?php

namespace GaspareJoubert\RandomPin;

class SetupPin implements iPIN
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
        $difference = 0;
        for ($i = 0; $i < $countPinArray - 1; $i++) {
            $difference += (int)abs($pinArray[$i] - $pinArray[$i + 1]);
        }

        if ($difference == $countPinArray - 1) {
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
