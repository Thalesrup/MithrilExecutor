<?php

namespace Test\MithrilExecutor;

use PHPUnit\Framework\TestCase;
use MithrilExecutor\BackgroundExecutor;
use Test\MithrilExecutor\TestClass;

class BackgroundExecutorTest extends TestCase
{
    private $backgroundInstance;

    public function testBackgroundExecutorWithFile(): void
    {
        $file = $this->mockedFile();

        $this->backgroundInstance = new BackgroundExecutor();
        $outputResult = $this->backgroundInstance
            ->withFile(
                $file,
                'ApiConsumer'
            )
            ->addMethod('clearLog')
            ->addMethod('fetchDataAndLog')
            ->addMethod('getLog')
            ->runNow()
            ->getOutPuts();

        $this->assertNotEmpty($outputResult);
        $this->assertOutPutsWithMethods(
            $outputResult,
            ['clearLog', 'fetchDataAndLog', 'getLog']
        );
    }

    public function testBackgroundExecutorWithInstanceOfClass(): void 
    {
        $this->backgroundInstance = new BackgroundExecutor();
        $outputResult = $this->backgroundInstance
            ->withConstruct(TestClass::class)
            ->addMethod('clearLog')
            ->addMethod('fetchDataAndLog')
            ->addMethod('getLog')
            ->runNow()
            ->getOutPuts();

        $this->assertNotEmpty($outputResult);
        $this->assertOutPutsWithMethods(
            $outputResult,
            ['clearLog', 'fetchDataAndLog', 'getLog']
        );
    }

    private function mockedFile()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_script_') . '.php';
        $phpContent = <<<'PHP'
<?php

class ApiConsumer
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
PHP;

        file_put_contents($tmpFile, $phpContent);
        chmod($tmpFile, 0755);

        return $tmpFile;
    }

    private function assertOutPutsWithMethods(array $outputResul, array $methods): void
    {
        foreach($methods as $method) {
            $this->assertNotEmpty($outputResul[$method]);
        }
    }
}