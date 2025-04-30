<?php

namespace NyCorp\LangHelperGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LangHelperWriter
{
    protected array $translations;

    public function __construct(array $translations)
    {
        $this->translations = $translations;
    }

    public function write(): void
    {
        $rootClass = "<?php\n\nnamespace App\Helpers;\n\nclass LangHelper\n{\n";

        $groups = [];

        foreach ($this->translations as $key => $value) {
            $parts = explode('.', $key);
            $group = Str::studly(array_shift($parts));
            $subPath = implode('.', $parts);

            $groups[$group][$subPath] = $key;
        }

        foreach ($groups as $groupName => $items) {
            $rootClass .= '    public static function '.lcfirst($groupName)."(): \\App\\Helpers\\Lang\\$groupName\\{$groupName}Translations\n    {\n";
            $rootClass .= "        return new \\App\\Helpers\\Lang\\$groupName\\{$groupName}Translations();\n    }\n\n";
            $this->generateGroup($groupName, $items);
        }

        $rootClass .= "}\n";

        File::ensureDirectoryExists(app_path('Helpers/Lang'));
        File::put(app_path('Helpers/LangHelper.php'), $rootClass);
    }

    protected function generateGroup($groupName, $items): void
    {
        $groupPath = app_path("Helpers/Lang/$groupName");
        File::ensureDirectoryExists($groupPath);

        $methods = '';

        $subGroups = [];

        foreach ($items as $subKey => $fullKey) {
            if (str_contains($subKey, '.')) {
                [$subgroup, $rest] = explode('.', $subKey, 2);
                $subGroups[$subgroup][$rest] = $fullKey;
            } else {
                $methodName = lcfirst(Str::studly($subKey));
                $methods .= $this->methodTemplate($methodName, $fullKey, parameters: $this->detectParameters($this->translations[$fullKey]));
            }
        }

        foreach ($subGroups as $subGroup => $subs) {
            $methods .= '    public function '.lcfirst(Str::studly($subGroup))."(): \\App\\Helpers\\Lang\\$groupName\\".Str::studly($subGroup)."Translations\n    {\n";
            $methods .= "        return new \\App\\Helpers\\Lang\\$groupName\\".Str::studly($subGroup)."Translations();\n    }\n\n";
            $this->generateSubGroup($groupName, $subGroup, $subs);
        }

        $content = "<?php\n\nnamespace App\Helpers\Lang\\$groupName;\n\nclass {$groupName}Translations\n{\n$methods}\n";
        File::put("$groupPath/{$groupName}Translations.php", $content);
    }

    public function methodTemplate(string $name, string $fullKey, array $parameters = [], string $returnType = ':string'): string
    {

        // Create method
        $paramsString = $parameters ? implode(', ', array_map(static fn ($p) => "string \${$p}", $parameters)) : '';
        $assocArray = $parameters ? '['.implode(', ', array_map(static fn ($p) => "'$p' => \${$p}", $parameters)).']' : '[]';

        $docParameters = '';
        if ($parameters) {
            foreach ($parameters as $param) {
                $docParameters .= "\n      * @param string \${$param}";
            }
        }

        return <<<METHOD
                        /**
                        *$docParameters
                        *
                        * @return string
                        */
                        public function $name($paramsString) $returnType{
                            return __('$fullKey',$assocArray);
                        }\n\n
                  METHOD;

    }

    protected function detectParameters(string $locale): array
    {
        $parameters = [];

        preg_match_all('/:(\w+)/', $locale, $matches);
        if (! empty($matches[1])) {
            $parameters = array_merge($parameters, $matches[1]);
        }

        return array_unique($parameters);
    }

    protected function generateSubGroup($groupName, $subGroup, $items): void
    {
        $subGroupPath = app_path("Helpers/Lang/$groupName");
        File::ensureDirectoryExists($subGroupPath);

        $methods = '';

        foreach ($items as $subKey => $fullKey) {
            $methodName = lcfirst(Str::studly(Str::replace('.', '_', $subKey)));
            $methods .= $this->methodTemplate($methodName, $fullKey, parameters: $this->detectParameters($this->translations[$fullKey]));
        }

        $content = "<?php\n\nnamespace App\Helpers\Lang\\$groupName;\n\nclass ".Str::studly($subGroup)."Translations\n{\n$methods}\n";
        File::put("$subGroupPath/".Str::studly($subGroup).'Translations.php', $content);
    }
}
