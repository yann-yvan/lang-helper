<?php

namespace NyCorp\LangHelperGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LangHelperGenerator
{
    protected array $translations = [];

    protected string $outputPath = 'app/Helpers/Lang/';

    public function generate(): void
    {
        $this->translations = [];
        $this->loadTranslations();
        (new LangHelperWriter($this->translations))->write();
    }

    protected function loadTranslations(): void
    {
        foreach (File::directories(lang_path()) as $localePath) {

            if (Str::endsWith($localePath, config('lang-helper.excluded_directories'))) {
                continue;
            }

            foreach (File::allFiles($localePath) as $file) {
                $group = str_replace('.php', '', $file->getFilename());

                if (Str::endsWith($group, config('lang-helper.excluded_lang_files'))) {
                    continue;
                }

                $translations = File::getRequire($file->getRealPath());
                $this->parseTranslations($translations, $group);
            }
        }
    }

    protected function parseTranslations(array $translations, string $prefix): void
    {
        foreach ($translations as $key => $value) {
            $fullKey = $prefix . '.' . $key;
            if (is_array($value)) {
                $this->parseTranslations($value, $fullKey);
            } else {
                $this->translations[$fullKey] = $value;
            }
        }
    }
}
