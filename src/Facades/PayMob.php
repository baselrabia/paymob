<?php

namespace  Basel\PayMob\Facades;

use Illuminate\Support\Facades\Facade;

class PayMob extends Facade
{
    protected static  function getFacadeAccessor()
    {
        return 'PayMob';
    }
}