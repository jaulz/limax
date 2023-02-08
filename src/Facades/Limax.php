<?php

namespace Jaulz\Limax\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jaulz\Limax\Limax
 */
class Limax extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Jaulz\Limax\Limax::class;
    }
}