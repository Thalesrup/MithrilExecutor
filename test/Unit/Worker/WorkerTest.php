<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Unit\Worker;

use MithrilExecutor\Contracts\JobQueueInterface;
use MithrilExecutor\Contracts\RunnerInterface;
use MithrilExecutor\Worker\Worker;
use MithrilExecutor\ValueObjects\Job;
use MithrilExecutor\ValueObjects\ExecutionReport;
use PHPUnit\Framework\TestCase;

class WorkerTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/mithril_worker_test_' . uniqid();
        mkdir($this->storage);
        mkdir($this->storage . '/logs');
    }

    protected function tearDown(): void
    {
        // cleanup code if needed
        $this->recursiveDelete($this->storage);
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

    public function testWorkOnceNoJob(): void
    {
        $queue = $this->createMock(JobQueueInterface::class);
        $queue->method('claimNext')->willReturn(null);
        $queue->method('storagePath')->willReturn($this->storage);

        $runner = $this->createMock(RunnerInterface::class);
        
        $worker = new Worker($queue, $runner);
        $this->assertFalse($worker->workOnce());
    }

    public function testWorkOnceSuccess(): void
    {
        $job = new Job('1', 'C', [], [], 0);
        $report = new ExecutionReport('1', true, 0, 1, []);

        $queue = $this->createMock(JobQueueInterface::class);
        $queue->method('claimNext')->willReturn($job);
        $queue->method('storagePath')->willReturn($this->storage);
        $queue->expects($this->once())->method('markDone')->with($job, $report, '', '');

        $runner = $this->createMock(RunnerInterface::class);
        $runner->method('run')->with($job)->willReturn([$report, '', '']);

        $worker = new Worker($queue, $runner);
        $this->assertTrue($worker->workOnce());
    }

    public function testWorkOnceFailure(): void
    {
        $job = new Job('1', 'C', [], [], 0);
        $report = new ExecutionReport('1', false, 0, 1, []);

        $queue = $this->createMock(JobQueueInterface::class);
        $queue->method('claimNext')->willReturn($job);
        $queue->method('storagePath')->willReturn($this->storage);
        // Expect markFailed because report->ok is false
        $queue->expects($this->once())->method('markFailed');

        $runner = $this->createMock(RunnerInterface::class);
        $runner->method('run')->with($job)->willReturn([$report, '', 'err']);

        $worker = new Worker($queue, $runner);
        $this->assertTrue($worker->workOnce());
    }
}
