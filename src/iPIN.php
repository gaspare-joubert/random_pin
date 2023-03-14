<?php

namespace GaspareJoubert\RandomPin;

interface iPIN
{
    /**
     * Is the PIN a palindrome, e.g. 2332.
     *
     * @param string $pin
     * @return bool
     */
    public function isPalindrome(string $pin): bool;

    /**
     * Is the PIN a sequential number, e.g. 1234.
     *
     * @param string $pin
     * @return bool
     */
    public function isSequential(string $pin): bool;

    /**
     * Is the PIN repeating numbers, e.g. 2233.
     *
     * @param string $pin
     * @return bool
     */
    public function isRepeating(string $pin): bool;
}

