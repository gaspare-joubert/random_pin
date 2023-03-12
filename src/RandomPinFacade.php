<?php

namespace GaspareJoubert\RandomPin;

use Illuminate\Support\Facades\Facade;

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
}
