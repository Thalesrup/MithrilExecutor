<?php
declare(strict_types=1);

namespace MithrilExecutor\Worker;

use MithrilExecutor\Contracts\JobQueueInterface;
use MithrilExecutor\Contracts\RunnerInterface;

final class Worker
{
    public function __construct(
        private readonly JobQueueInterface $queue,
        private readonly RunnerInterface $runner,
        private readonly string $logFile = 'worker.log',
        public ?\Closure $onLog = null,
    ) {}

    public function workOnce(): bool
    {
        $job = $this->queue->claimNext();
        if ($job === null) {
            return false;
        }

        $this->log("Claimed job {$job->id} ({$job->className})");

        try {
            [$report, $stdout, $stderr] = $this->runner->run($job);

            if ($report->ok) {
                $this->queue->markDone($job, $report, $stdout, $stderr);
                $this->log("Done job {$job->id}");
            } else {
                // Mesmo com falha em um método, tratamos como failed (ou você pode decidir o contrário).
                $this->queue->markFailed($job, new \RuntimeException('One or more calls failed'), $stdout, $stderr);
                $this->log("Failed job {$job->id} (one or more calls failed)");
            }
        } catch (\Throwable $e) {
            $this->queue->markFailed($job, $e, '', '');
            $this->log("Critical fail job {$job->id}: {$e->getMessage()}");
        }

        return true;
    }

    /**
     * Daemon mode: loop infinito, dorme quando não tem job.
     * @param int $sleepMs
     */
    public function daemon(int $sleepMs = 250): void
    {
        $this->log("Daemon started (sleepMs={$sleepMs})");

        $running = true;

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function() use (&$running) { $running = false; });
            pcntl_signal(SIGINT, function() use (&$running) { $running = false; });
        }

        while ($running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $worked = $this->workOnce();

            if (!$worked) {
                usleep(max(10_000, $sleepMs * 1000));
            }
        }

        $this->log("Daemon stopped");
    }

    private function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($this->queue->storagePath() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $this->logFile, $line, FILE_APPEND);

        if ($this->onLog) {
            ($this->onLog)($message);
        }
    }
}
