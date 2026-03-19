<?php

declare(strict_types=1);

namespace Egough\Holocron\Facades;

use Illuminate\Support\Facades\Facade;

class Holocron extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Egough\Holocron\Holocron::class;
    }
}
