<?php

namespace GaspareJoubert\RandomPin;

class ValidatePIN extends PIN
{
    /**
     * If any of the test conditions returns true, the pin has failed validation.
     * Return 'fail'.
     *
     * @return string
     */
    public function validatePin(): string
    {
        $tests = get_object_vars($this) ?? '';

        if (!$tests) {
            return 'fail';
        }

        foreach ($tests as $key => $value) {
            if ($value === true) {
                return 'fail';
            }
        }

        return 'pass';
    }
}

