<?php

namespace NyCorp\LangHelperGenerator;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LangHelperWriter
{
    protected array $translations;
    private string $namespace = 'App\Helpers\Lang';

    public function __construct(array $translations)
    {
        $this->translations = $translations;
    }


    public function write(): void
    {


        $groups = [];

        foreach ($this->translations as $key => $value) {
            $parts = explode('.', $key);
            $group = Str::studly(array_shift($parts));
            $subPath = implode('.', $parts);

            $groups[$group][$subPath] = $key;
        }

        $methods = "";
        foreach ($groups as $groupName => $items) {

            $className = lcfirst($groupName);
            $methods .= <<<CLASS
                             public static function $className(): \\App\\Helpers\\Lang\\$groupName\\{$groupName}Translations
                             {
                                return new \\App\\Helpers\\Lang\\$groupName\\{$groupName}Translations();
                             }


                            CLASS;

            $this->generateGroup($groupName, $items);
        }


        $content = <<<CLASS
                      <?php

                      namespace App\Helpers;

                      use $this->namespace\LangSafety;

                      class LangHelper
                      {
                        use LangSafety;

                        $methods
                      }

                      CLASS;


        File::ensureDirectoryExists(app_path('Helpers/Lang'));
        File::put(app_path('Helpers/LangHelper.php'), $content);
        $this->generateSafetyClass();
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

            $methodName = lcfirst(Str::studly($subGroup));
            $classPath = "\\App\\Helpers\\Lang\\$groupName\\" . Str::studly($subGroup) . "Translations";
            $methods .= <<<CLASS
                                 public function $methodName(): $classPath
                                 {
                                    return new $classPath();
                                 }

                            CLASS;

            $this->generateSubGroup($groupName, $subGroup, $subs);
        }

        $content = <<<CLASS
                     <?php

                     namespace App\Helpers\Lang\\$groupName;

                     use $this->namespace\LangSafety;

                     class {$groupName}Translations
                     {
                        use LangSafety;

                        $methods
                     }

                     CLASS;


        File::put("$groupPath/{$groupName}Translations.php", $content);
    }

    public function methodTemplate(string $name, string $fullKey, array $parameters = [], string $returnType = ': string'): string
    {

        // Create method
        $paramsString = $parameters ? implode(', ', array_map(static fn($p) => "string \${$p}", $parameters)) : '';
        $assocArray = $parameters ? '[' . implode(', ', array_map(static fn($p) => "'$p' => \${$p}", $parameters)) . ']' : '[]';

        $docParameters = '';
        if ($parameters) {
            foreach ($parameters as $param) {
                $docParameters .= "\n\t * @param string \${$param}";
            }
        }

        return <<<METHOD
                      /**$docParameters
                       *
                       * @return string
                       */
                      public function $name($paramsString)$returnType
                      {
                          return __('$fullKey', $assocArray);
                      }


                  METHOD;

    }

    protected function detectParameters(string $locale): array
    {
        $parameters = [];

        preg_match_all('/:(\w+)/', $locale, $matches);
        if (!empty($matches[1])) {
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

        $classGroup = Str::studly($subGroup);
        $content = <<<CLASS
                      <?php

                      namespace $this->namespace\\$groupName;

                      use $this->namespace\LangSafety;

                      class {$classGroup}Translations
                      {
                        use LangSafety;

                        $methods
                      }

                      CLASS;

        File::put("$subGroupPath/" . Str::studly($subGroup) . 'Translations.php', $content);
    }

    public function generateSafetyClass(): void
    {
        $content = <<<CLASS
                        <?php

                        namespace $this->namespace;

                        trait LangSafety {

                            public function __construct(private array \$path = [],private array \$parameters =[])
                            {
                            }

                            public static function __callStatic(\$method, \$parameters)
                            {
                                return (new static)->__call(\$method, \$parameters);
                            }

                            public function __call(\$method, \$parameters)
                            {
                                \$this->path[] =  \$method;
                                \$this->parameters = \$parameters;
                                return \$this;
                            }

                            public function __toString()
                            {
                                return __(implode('.', \$this->path),\$this->parameters);
                            }
                        }


                        CLASS;

        File::put(app_path("Helpers/Lang/LangSafety.php"), $content);
    }
}
