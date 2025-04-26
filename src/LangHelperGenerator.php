<?php

namespace NyCorp\LangHelperGenerator;

use Illuminate\Support\Facades\File;

class LangHelperGenerator
{
    protected array $translations = [];

    protected string $outputPath = 'app/Helpers/Lang/';

    public function generate()
    {
        $this->translations = [];
        $this->loadTranslations();
        (new LangHelperWriter($this->translations))->write();
    }

    protected function loadTranslations()
    {
        foreach (File::directories(lang_path()) as $localePath) {
            foreach (File::allFiles($localePath) as $file) {
                $group = str_replace('.php', '', $file->getFilename());
                $translations = File::getRequire($file->getRealPath());
                $this->parseTranslations($translations, $group);
            }
        }
    }

    protected function parseTranslations(array $translations, string $prefix): void
    {
        foreach ($translations as $key => $value) {
            $fullKey = $prefix.'.'.$key;
            if (is_array($value)) {
                $this->parseTranslations($value, $fullKey);
            } else {
                $this->translations[$fullKey] = $value;
            }
        }
    }
}
