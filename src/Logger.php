<?php

namespace MithrilExecutor;

class Logger
{
    private string $logFilePath;
    private string $errorLogFilePath;
    private string $serializedFile;
    private int $timeout = 30;
    private int|float $interval = 1;

    public function __construct()
    {
        $tempDir = sys_get_temp_dir();
        $this->logFilePath = tempnam($tempDir, 'logfile_') . '.txt';
        $this->errorLogFilePath = tempnam($tempDir, 'errorLogfile_') . '.txt';
        $this->serializedFile = tempnam($tempDir, 'background_executor_output_') . '.ser';
    }

    public function writeLog(string $message): void
    {
        file_put_contents($this->logFilePath, $message . PHP_EOL, FILE_APPEND);
    }

    public function writeErrorLog(string $message): void
    {
        file_put_contents($this->errorLogFilePath, $message . PHP_EOL, FILE_APPEND);
    }

    public function getLogs(): array
    {
        if (!file_exists($this->logFilePath)) {
            return ["Log file does not exist."];
        }
        return file($this->logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function getSerializeFilePath(): string
    {
        return $this->serializedFile;
    }

    public function clearLogs(): void
    {
        if (file_exists($this->logFilePath)) {
            file_put_contents($this->logFilePath, '');
        }
    }

    public function clearErrorLogs(): void
    {
        if (file_exists($this->errorLogFilePath)) {
            file_put_contents($this->errorLogFilePath, '');
        }
    }

    public function getLogPath(): string
    {
        return $this->logFilePath;
    }

    public function getErrorLogPath(): string
    {
        return $this->errorLogFilePath;
    }

    public function setPresetWaitFileGenerate(int $timeout, int|float $interval): void
    {
        $this->timeout = $timeout;
        $this->interval = $interval;
    }

    public function getOutPuts(): array
    {
        $startTime = time();
        while (true) {
            if (file_exists($this->serializedFile) 
                && !empty(file_get_contents($this->serializedFile))
            ) {
                return unserialize(file_get_contents($this->serializedFile));
            }
            
            if (time() - $startTime > $this->timeout) {
                throw new \Exception("Timeout: O arquivo não foi encontrado ou está vazio.");
            }

            sleep($this->interval);
        }

        return [];
    }

    public function unlinAllFilePath(): void
    {
        unlink($this->logFilePath);
        unlink($this->errorLogFilePath);
        unlink($this->serializedFile);
    }

}
