<?php

namespace GaspareJoubert\RandomPin;

class Pin
{
    public $iPIN;
    public $isPalindrome;
    public $isSequential;
    public $isRepeating;

    public function __construct(iPin $iPIN, string $pin)
    {
        $this->iPIN = $iPIN;
        $this->isPalindrome = $this->iPIN->isPalindrome($pin);
        $this->isSequential = $this->iPIN->isSequential($pin);
        $this->isRepeating = $this->iPIN->isRepeating($pin);
    }
}

