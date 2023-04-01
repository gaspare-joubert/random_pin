<?php

namespace GaspareJoubert\RandomPin;

class SetupPIN implements iPIN
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
        $pinArrayLength = count($pinArray);

        $testArray = [];
        for ($i = 0; $i < $pinArrayLength; $i++) {
            if ($i === 0) {
                $testArray[$i] = $pinArray[0];
            } else {
                $testArray[$i] = (string)($testArray[$i - 1] + 1);
            }
        }
        $testString = implode($testArray);

        if ($pin === $testString) {
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
