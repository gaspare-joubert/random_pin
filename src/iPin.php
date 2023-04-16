<?php

namespace GaspareJoubert\RandomPin;

interface iPin
{
    /**
     * Is the Pin a palindrome, e.g. 2332.
     *
     * @param string $pin
     * @return bool
     */
    public function isPalindrome(string $pin): bool;

    /**
     * Is the Pin a sequential number, e.g. 1234, 4321, 1235 or 5321.
     *
     * @param string $pin
     * @return bool
     */
    public function isSequential(string $pin): bool;

    /**
     * Is the Pin repeating numbers, e.g. 2233.
     * Allow repeating numbers when the Pin length is 5 or more.
     *
     * @param string $pin
     * @return bool
     */
    public function isRepeating(string $pin): bool;
}

