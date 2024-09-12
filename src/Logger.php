<?php

namespace MithrilExecutor;

class Logger
{
    private string $logFilePath;
    private string $errorLogFilePath;
    private string $serializedFile;

    public function __construct(string $logFilePath = './logfile.txt', string $errorLogFilePath = './errorLogfile.txt')
    {
        $this->logFilePath = $logFilePath;
        $this->errorLogFilePath = $errorLogFilePath;
        $this->serializedFile = sys_get_temp_dir() . '/background_executor_output.ser';
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

    public function getOutPuts(): array
    {
        if (file_exists($this->serializedFile)) {
            return unserialize(file_get_contents($this->serializedFile));
        }
    }
}
