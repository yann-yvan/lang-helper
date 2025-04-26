<?php

namespace NyCorp\LangHelperGenerator;

class UnusedTranslationDetector
{

    public function detect(): void
    {
        $helperPath = app_path('Helpers/LangHelper.php');

        if (!file_exists($helperPath)) {
            echo "LangHelper.php not found.\n";
            return;
        }

        preg_match_all('/function (\w+)\(/', file_get_contents($helperPath), $matches);
        $methods = $matches[1] ?? [];

        $files = $this->getPhpFiles(app_path());
        $files = array_merge($files, $this->getPhpFiles(resource_path('views')));

        $used = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            foreach ($methods as $method) {
                if (strpos($content, "LangHelper::$method(") !== false) {
                    $used[] = $method;
                }
            }
        }

        $unused = array_diff($methods, $used);

        if (empty($unused)) {
            echo "✅ No unused translation methods found.\n";
        } else {
            echo "⚠️ Unused translation methods:\n";
            foreach ($unused as $method) {
                echo "- $method\n";
            }
        }
    }

    protected function getPhpFiles(string $path): array
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = [];

        foreach ($rii as $file) {
            if (!$file->isDir() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
