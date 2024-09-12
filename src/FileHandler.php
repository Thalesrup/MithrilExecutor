<?php

namespace MithrilExecutor;

class FileHandler
{
    public function createTempFile(): string
    {
        return tempnam(sys_get_temp_dir(), 'php_script_') . '.php';
    }

    public function writeToFile(string $filePath, string $content): void
    {
        file_put_contents($filePath, $content);
    }
}
