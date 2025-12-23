<?php
declare(strict_types=1);

namespace MithrilExecutor\Supervisor;

use MithrilExecutor\Contracts\JobQueueInterface;

final class ProcessSupervisor
{
    /** @var array<int, resource> */
    private array $processes = [];

    public function __construct(
        private readonly JobQueueInterface $queue,
        private readonly string $workerCommand,
        private readonly int $concurrency,
        public ?\Closure $onLog = null,
    ) {}

    public function run(int $sleepMs): void
    {
        $this->log("Supervisor started with concurrency {$this->concurrency}");
        
        $running = true;

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function() use (&$running) { $running = false; });
            pcntl_signal(SIGINT, function() use (&$running) { $running = false; });
        }

        while ($running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $this->checkProcesses();

            // Se temos vagas e tem jobs pendentes
            if (count($this->processes) < $this->concurrency) {
                if ($this->queue->hasPending()) {
                    $this->spawnWorker();
                } else {
                    // Se fila vazia e sem processos rodando, dorme um pouco
                    // Se tem processos rodando, não dorme muito pois pode liberar vaga logo
                    if (empty($this->processes)) {
                        usleep(max(10_000, $sleepMs * 1000));
                    } else {
                        usleep(10_000); // 10ms check rápido
                    }
                }
            } else {
                // Full capacity, wait a bit
                usleep(50_000);
            }
        }
        
        $this->log("Supervisor stopping. Waiting for children...");
        while (!empty($this->processes)) {
            $this->checkProcesses();
            usleep(100_000);
        }
        $this->log("Supervisor stopped.");
    }

    private function spawnWorker(): void
    {
        // Inherit stdout/stderr for visibility
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => STDOUT,
            2 => STDERR,
        ];

        // Adiciona timestamp ou ID para log
        // Executa: php bin/mithril work ...
        $process = proc_open($this->workerCommand, $descriptors, $pipes);

        if (is_resource($process)) {
            // Pipes 1 and 2 are not available since we used STDOUT/STDERR
            fclose($pipes[0]);

            // Pega o ID (pid)
            $status = proc_get_status($process);
            $pid = $status['pid'];
            
            $this->processes[$pid] = $process;
            // $this->log("Spawned worker PID {$pid}");
        } else {
            $this->log("Failed to spawn worker process");
        }
    }

    private function checkProcesses(): void
    {
        foreach ($this->processes as $pid => $process) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = $status['exitcode'];
                proc_close($process);
                unset($this->processes[$pid]);
                // $this->log("Worker PID {$pid} finished with code {$exitCode}");
            }
        }
    }

    private function log(string $msg): void
    {
        if ($this->onLog) {
            ($this->onLog)($msg);
        }
    }
}
