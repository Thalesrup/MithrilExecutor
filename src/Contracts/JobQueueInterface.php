<?php
declare(strict_types=1);

namespace MithrilExecutor\Contracts;

use MithrilExecutor\ValueObjects\Job;

interface JobQueueInterface
{
    public function enqueue(Job $job): string; // retorna jobId
    public function claimNext(): ?Job;         // pega e marca running (atômico via rename)
    public function markDone(Job $job, JobResult $result, string $stdout, string $stderr): void;
    public function markFailed(Job $job, \Throwable $e, string $stdout, string $stderr): void;
    public function hasPending(): bool;
    /** @return array<int, array{id: string, status: string, class: string, created_at: string}> */
    public function list(string $status = 'all'): array;
    public function storagePath(): string;
}

interface JobResult
{
    public function toArray(): array;
}
