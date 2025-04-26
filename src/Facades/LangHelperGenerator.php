<?php

namespace NyCorp\LangHelperGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NyCorp\LangHelperGenerator\LangHelperGenerator
 */
class LangHelperGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NyCorp\LangHelperGenerator\LangHelperGenerator::class;
    }
}
