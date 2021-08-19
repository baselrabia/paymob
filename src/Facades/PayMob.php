<?php

namespace Basel\PayMob\Facades;

use Illuminate\Support\Facades\Facade;

class PayMob extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'paymob';
    }
}
