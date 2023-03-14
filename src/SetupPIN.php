<?php

namespace GaspareJoubert\RandomPin;

class SetupPIN implements iPIN
{
    /**
     * @inheritDoc
     */
    public function isPalindrome(string $pin): bool
    {
        if ($pin === strrev($pin)) {
            return true;
        }

        return false;
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
        foreach (count_chars($pin, 1) as $i => $val) {
            if ($val > 1) {
                return true;
            }
        }

        return false;
    }
}
