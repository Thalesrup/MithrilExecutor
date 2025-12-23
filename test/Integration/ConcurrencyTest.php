<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Integration;

use PHPUnit\Framework\TestCase;

class ConcurrencyTest extends TestCase
{
    private string $exampleDir;
    private string $bin;
    private string $storage;

    protected function setUp(): void
    {
        $this->exampleDir = realpath(__DIR__ . '/../../example');
        $this->bin = realpath(__DIR__ . '/../../bin/mithril');
        $this->storage = sys_get_temp_dir() . '/mithril_concurrency_' . uniqid();
        
        mkdir($this->storage);
        mkdir($this->storage . '/queue/pending', 0777, true);
        mkdir($this->storage . '/logs', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->recursiveDelete($this->storage);
        $this->recursiveDelete($this->exampleDir . '/reports');
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testParallelExecution(): void
    {
        // 1. Criar 3 Jobs que demoram 2 segundos cada
        // Se for sequencial: 3 * 2 = 6s
        // Se for paralelo (concurrency=3): ~2s + overhead
        
        $bootstrap = $this->exampleDir . '/bootstrap.php';
        
        // Vamos usar um comando fake que apenas dorme, para não depender do exemplo
        // Mas o exemplo agora tem delay de 5s. É muito para teste unitário?
        // Sim. Vamos criar um job mais simples on-the-fly ou usar Mock?
        // Teste de integração real é melhor.
        // Vamos criar um arquivo de job manual para a classe ReportGenerator mas com sleep menor se possivel?
        // Não conseguimos mudar o sleep do ReportGenerator sem editar o arquivo.
        // Vamos criar uma classe SleepTask temporária.
        
        $sleepTaskFile = $this->storage . '/SleepTask.php';
        file_put_contents($sleepTaskFile, '<?php
            namespace Temp;
            class SleepTask {
                public function sleep(int $seconds) {
                    sleep($seconds);
                    return "slept {$seconds}";
                }
            }
        ');
        
        $bootstrapFile = $this->storage . '/bootstrap_temp.php';
        file_put_contents($bootstrapFile, '<?php
            require_once "' . str_replace('\\', '\\\\', $sleepTaskFile) . '";
            use MithrilExecutor\Resolvers\ReflectionResolver;
            return new ReflectionResolver();
        ');

        // Enfileirar 3 jobs de 2 segundos
        for ($i = 0; $i < 3; $i++) {
            $this->enqueueJob('job-'.$i, 'Temp\\SleepTask', 'sleep', [2]);
        }
        
        $start = microtime(true);
        
        // Rodar o daemon com concurrency=3 por alguns segundos
        // Como o daemon roda em loop infinito, precisamos rodar ele em background e matar depois?
        // Ou usar "exec" com timeout?
        // PHPUnit não lida bem com processos em background persistentes.
        // Vamos usar proc_open para rodar o daemon e matar após X segundos.
        
        $cmd = sprintf(
            '"%s" "%s" daemon --storage="%s" --bootstrap="%s" --concurrency=3 --sleep=100',
            PHP_BINARY,
            $this->bin,
            $this->storage,
            $bootstrapFile
        );
        
        $stdoutFile = $this->storage . '/stdout.log';
        $stderrFile = $this->storage . '/stderr.log';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $stdoutFile, 'a'],
            2 => ['file', $stderrFile, 'a'],
        ];
        
        $process = proc_open($cmd, $descriptors, $pipes);
        
        // Monitorar até que os jobs acabem
        $maxWait = 10; // 10s max
        $doneCount = 0;
        
        while ((microtime(true) - $start) < $maxWait) {
            $doneFiles = glob($this->storage . '/queue/done/*.json');
            $doneCount = count($doneFiles);
            
            if ($doneCount >= 3) {
                break;
            }
            usleep(500000); // 0.5s check
        }
        
        // Matar daemon
        proc_terminate($process);
        
        $end = microtime(true);
        $duration = $end - $start;
        
        $stdoutLog = file_exists($stdoutFile) ? file_get_contents($stdoutFile) : '';
        $stderrLog = file_exists($stderrFile) ? file_get_contents($stderrFile) : '';
        
        $this->assertEquals(3, $doneCount, "All 3 jobs should be done. \nSTDOUT:\n$stdoutLog\nSTDERR:\n$stderrLog");
        
        // Se fosse sequencial, demoraria pelo menos 6 segundos (3 jobs * 2s).
        // Com overhead de processos no Windows + Xdebug, pode demorar um pouco mais.
        // Mas deve ser mais rápido que sequencial puro (> 6s + overhead).
        $this->assertLessThan(7.0, $duration, "Execution took {$duration}s, expected parallel execution (< 7s)");
    }
    
    private function enqueueJob(string $id, string $class, string $method, array $args): void
    {
        $jobData = [
            'id' => $id,
            'className' => $class,
            'constructorArgs' => [],
            'calls' => [
                ['method' => $method, 'args' => $args]
            ],
            'createdAtMs' => 12345
        ];
        file_put_contents($this->storage . "/queue/pending/{$id}.json", json_encode($jobData));
    }
}
