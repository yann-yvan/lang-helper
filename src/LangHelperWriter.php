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

    public function write()
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

    protected function generateGroup($groupName, $items)
    {
        $groupPath = app_path("Helpers/Lang/$groupName");
        File::ensureDirectoryExists($groupPath);

        $methods = '';

        $subGroups = [];

        foreach ($items as $subKey => $fullKey) {
            if (strpos($subKey, '.') !== false) {
                [$subgroup, $rest] = explode('.', $subKey, 2);
                $subGroups[$subgroup][$rest] = $fullKey;
            } else {
                $methodName = lcfirst(Str::studly($subKey));
                $methods .= "    public function $methodName(): string\n    {\n";
                $methods .= "        return __('$fullKey');\n    }\n\n";
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

    protected function generateSubGroup($groupName, $subGroup, $items)
    {
        $subGroupPath = app_path("Helpers/Lang/$groupName");
        File::ensureDirectoryExists($subGroupPath);

        $methods = '';

        foreach ($items as $subKey => $fullKey) {
            $methodName = lcfirst(Str::studly(Str::replace('.', '_', $subKey)));
            $methods .= "    public function $methodName(): string\n    {\n";
            $methods .= "        return __('$fullKey');\n    }\n\n";
        }

        $content = "<?php\n\nnamespace App\Helpers\Lang\\$groupName;\n\nclass ".Str::studly($subGroup)."Translations\n{\n$methods}\n";
        File::put("$subGroupPath/".Str::studly($subGroup).'Translations.php', $content);
    }
}
