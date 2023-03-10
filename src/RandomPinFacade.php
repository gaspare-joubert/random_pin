<?php

namespace GaspareJoubert\RandomPin;

use Illuminate\Support\Facades\Facade;

/**
 * @see \GaspareJoubert\RandomPin\Skeleton\SkeletonClass
 */
class RandomPinFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'random_pin';
    }
}
