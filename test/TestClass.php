<?php

namespace Test\MithrilExecutor;

class TestClass
{
    private string $apiUrl;
    private string $logFile;

    public function __construct()
    {
        $this->apiUrl = 'https://randomuser.me/api/';
        $this->logFile = __DIR__ . '/api_log.txt';
        $this->clearLog();
    }

    private function clearLog()
    {
        file_put_contents($this->logFile, '');
    }

    public function fetchDataAndLog()
    {
        $response = file_get_contents($this->apiUrl);

        if ($response === false) {
            $this->logError("Erro ao consumir a API.");
            return;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError("Erro ao decodificar a resposta JSON.");
            return;
        }

        $this->logResult($data);
    }

    private function logResult($data)
    {
        $logFile = __DIR__ . '/api_log.txt';

        $logData = sprintf(
            "[%s] Nome: %s %s, Email: %s\n",
            date('Y-m-d H:i:s'),
            $data['results'][0]['name']['first'],
            $data['results'][0]['name']['last'],
            $data['results'][0]['email']
        );

        file_put_contents($logFile, $logData, FILE_APPEND);
    }

    private function logError($message)
    {
        $logFile = __DIR__ . '/api_log.txt';
        $logData = sprintf("[%s] ERRO: %s\n", date('Y-m-d H:i:s'), $message);
        file_put_contents($logFile, $logData, FILE_APPEND);
    }

    public function getLog()
    {
        if (!file_exists($this->logFile)) {
            return "O arquivo de log não existe.";
        }

        $logContent = file_get_contents($this->logFile);
        return $logContent !== false ? $logContent : "Erro ao ler o arquivo de log.";
    }
}