<?php

namespace NyCorp\LangHelperGenerator;

use NyCorp\LangHelperGenerator\Commands\LangHelperGeneratorCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LangHelperGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('lang-helper')
            ->hasConfigFile()
            // ->hasViews()
            // ->hasMigration('create_lang_helper_table')
            ->hasCommand(LangHelperGeneratorCommand::class);
    }
}
