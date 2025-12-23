<?php
declare(strict_types=1);

namespace MithrilExecutor\Queue;

use MithrilExecutor\Contracts\JobQueueInterface;
use MithrilExecutor\Contracts\JobResult;
use MithrilExecutor\ValueObjects\Job;

final class FileJobQueue implements JobQueueInterface
{
    private string $storage;

    public function __construct(string $storagePath)
    {
        $this->storage = rtrim($storagePath, DIRECTORY_SEPARATOR);

        $this->ensureDirs();
    }

    public function storagePath(): string
    {
        return $this->storage;
    }

    public function enqueue(Job $job): string
    {
        $pendingDir = $this->path('queue/pending');
        $jobFile = $pendingDir . DIRECTORY_SEPARATOR . $job->id . '.json';

        $payload = json_encode($job->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($payload === false) {
            throw new \RuntimeException('Failed to encode job JSON');
        }

        // write atomically
        $tmp = $jobFile . '.tmp.' . bin2hex(random_bytes(4));
        file_put_contents($tmp, $payload);
        rename($tmp, $jobFile);

        return $job->id;
    }

    public function claimNext(): ?Job
    {
        $pendingDir = $this->path('queue/pending');
        $runningDir = $this->path('queue/running');

        $files = glob($pendingDir . DIRECTORY_SEPARATOR . '*.json') ?: [];
        sort($files); // FIFO by filename (id starts with timestamp)

        foreach ($files as $file) {
            $base = basename($file);
            $target = $runningDir . DIRECTORY_SEPARATOR . $base;

            // claim atomically: rename succeeds for only one worker
            if (@rename($file, $target)) {
                $raw = file_get_contents($target);
                if ($raw === false) {
                    // move to failed if can't read
                    @rename($target, $this->path('queue/failed') . DIRECTORY_SEPARATOR . $base);
                    continue;
                }
                $data = json_decode($raw, true);
                if (!is_array($data)) {
                    @rename($target, $this->path('queue/failed') . DIRECTORY_SEPARATOR . $base);
                    continue;
                }
                return Job::fromArray($data);
            }
        }

        return null;
    }

    public function hasPending(): bool
    {
        $pendingDir = $this->path('queue/pending');
        $files = glob($pendingDir . DIRECTORY_SEPARATOR . '*.json');
        return !empty($files);
    }

    public function list(string $status = 'all'): array
    {
        $statuses = $status === 'all' ? ['pending', 'running', 'done', 'failed'] : [$status];
        $jobs = [];

        foreach ($statuses as $s) {
            $dir = $this->path("queue/{$s}");
            $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
            
            if (!$files) continue;

            foreach ($files as $file) {
                $raw = @file_get_contents($file);
                if (!$raw) continue;
                
                $data = json_decode($raw, true);
                if (!is_array($data)) continue;

                $jobs[] = [
                    'id' => $data['id'] ?? basename($file, '.json'),
                    'status' => $s,
                    'class' => $data['className'] ?? 'Unknown',
                    'created_at' => isset($data['createdAtMs']) ? date('Y-m-d H:i:s', (int) ($data['createdAtMs'] / 1000)) : 'N/A',
                ];
            }
        }

        return $jobs;
    }

    public function markDone(Job $job, JobResult $result, string $stdout, string $stderr): void
    {
        $this->persistResult($job->id, $result->toArray(), 'done', $stdout, $stderr);
        $this->moveOutOfRunning($job->id, 'done');
    }

    public function markFailed(Job $job, \Throwable $e, string $stdout, string $stderr): void
    {
        $payload = [
            'jobId' => $job->id,
            'ok' => false,
            'error' => $e->getMessage(),
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
        ];
        $this->persistResult($job->id, $payload, 'failed', $stdout, $stderr);
        $this->moveOutOfRunning($job->id, 'failed');
    }

    private function persistResult(string $jobId, array $result, string $status, string $stdout = '', string $stderr = ''): void
    {
        $dir = $this->path("results/{$jobId}");
        @mkdir($dir, 0777, true);

        $result['status'] = $status;
        $result['savedAtMs'] = (int) floor(microtime(true) * 1000);

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'result.json', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'stdout.log', $stdout);
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'stderr.log', $stderr);
    }

    private function moveOutOfRunning(string $jobId, string $finalDir): void
    {
        $runningDir = $this->path('queue/running');
        $src = $runningDir . DIRECTORY_SEPARATOR . $jobId . '.json';
        $dst = $this->path("queue/{$finalDir}") . DIRECTORY_SEPARATOR . $jobId . '.json';

        if (is_file($src)) {
            @rename($src, $dst);
        }
    }

    private function ensureDirs(): void
    {
        foreach ([
            'queue/pending',
            'queue/running',
            'queue/done',
            'queue/failed',
            'results',
            'logs',
        ] as $dir) {
            @mkdir($this->path($dir), 0777, true);
        }
    }

    private function path(string $relative): string
    {
        return $this->storage . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    }
}
