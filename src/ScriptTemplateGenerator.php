<?php

namespace MithrilExecutor;

class ScriptTemplateGenerator
{
    public function buildPhpTemplate(
        string $className,
        array $params,
        array $methods,
        string $logFilePath,
        string $errorLogFilePath,
        ?string $pathFile = null
    ): string {
        $serializedParams = serialize($params);
        $serializedMethods = serialize($methods);
        $requireFile = $pathFile ? "require_once '$pathFile';" : "";

        return <<<PHP
        <?php
        require "../vendor/autoload.php";
        $requireFile
        
        \$args = unserialize('$serializedParams');
        \$methods = unserialize('$serializedMethods');
        
        \$className = '$className';
        \$instance = new \$className(...\$args);

        \$reflectionClass = new ReflectionClass(\$className);
        foreach (\$methods as \$method) {
            \$methodName = \$method['method'];
            \$methodArgs = \$method['args'];
            
            if (\$reflectionClass->hasMethod(\$methodName)) {
                \$reflectionMethod = \$reflectionClass->getMethod(\$methodName);
                \$reflectionMethod->setAccessible(true);

                ob_start();
                \$returnValue = \$reflectionMethod->invokeArgs(\$instance, \$methodArgs);
                \$output = ob_get_clean();

                \$methodOutputs[\$methodName] = [
                    'return' => \$returnValue,
                    'output' => \$output
                ];

            } else {
                file_put_contents('$errorLogFilePath', "Method \$methodName does not exist in class \$className" . PHP_EOL, FILE_APPEND);
            }
        }
        
        \$serializedOutputs = serialize(\$methodOutputs);
        file_put_contents('$logFilePath', "Process started [\$className] at: " . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
        file_put_contents(sys_get_temp_dir() . '/background_executor_output.ser', \$serializedOutputs);
        PHP;
    }

    public function buildPhpTemplateWithFile(
        string $className,
        array $params,
        array $methods,
        string $pathFile,
        string $logFilePath,
        string $errorLogFilePath
    ): string {
        return $this->buildPhpTemplate(
            $className,
            $params,
            $methods,
            $logFilePath,
            $errorLogFilePath,
            $pathFile
        );
    }
}
