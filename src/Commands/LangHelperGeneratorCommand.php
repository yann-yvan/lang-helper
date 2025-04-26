<?php

namespace NyCorp\LangHelperGenerator\Commands;


use Illuminate\Console\Command;
use NyCorp\LangHelperGenerator\LangHelperGenerator;
use NyCorp\LangHelperGenerator\UnusedTranslationDetector;

class LangHelperGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:generate {--detect-unused}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate LangHelper class from language files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔵 Generating LangHelper...');

        $generator = new LangHelperGenerator();
        $generator->generate();

        $this->info('✅ LangHelper generated successfully.');

        if ($this->option('detect-unused')) {
            $this->warn('🧹 Detecting unused translations...');
            $detector = new UnusedTranslationDetector();
            $detector->detect();
        }
    }
}
