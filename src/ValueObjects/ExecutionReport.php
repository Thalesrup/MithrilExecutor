<?php
declare(strict_types=1);

namespace MithrilExecutor\ValueObjects;

use MithrilExecutor\Contracts\JobResult;

final class ExecutionReport implements JobResult
{
    /**
     * @param array<string, array{ok:bool, return:mixed, error?:string, durationMs:int}> $calls
     */
    public function __construct(
        public readonly string $jobId,
        public readonly bool $ok,
        public readonly int $startedAtMs,
        public readonly int $finishedAtMs,
        public readonly array $calls,
    ) {}

    public function toArray(): array
    {
        return [
            'jobId' => $this->jobId,
            'ok' => $this->ok,
            'startedAtMs' => $this->startedAtMs,
            'finishedAtMs' => $this->finishedAtMs,
            'durationMs' => $this->finishedAtMs - $this->startedAtMs,
            'calls' => $this->calls,
        ];
    }
}
