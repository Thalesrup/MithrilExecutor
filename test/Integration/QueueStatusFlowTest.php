<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Integration;

use MithrilExecutor\Contracts\JobQueueInterface;
use MithrilExecutor\Contracts\RunnerInterface;
use MithrilExecutor\Queue\FileJobQueue;
use MithrilExecutor\ValueObjects\ExecutionReport;
use MithrilExecutor\ValueObjects\Job;
use MithrilExecutor\Worker\Worker;
use PHPUnit\Framework\TestCase;

class QueueStatusFlowTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/mithril_status_flow_' . uniqid();
        mkdir($this->storage);
    }

    protected function tearDown(): void
    {
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

    public function testJobLifecycleSuccess(): void
    {
        // 1. Setup components
        $queue = new FileJobQueue($this->storage);
        $jobId = 'lifecycle-job-success';
        
        // 2. Enqueue Job (PENDING)
        $job = new Job($jobId, 'MyClass', [], [], time() * 1000);
        $queue->enqueue($job);

        // Verify PENDING state
        $this->assertFileExists($this->storage . "/queue/pending/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/running/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/done/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/failed/{$jobId}.json");

        // 3. Prepare Runner Mock to verify RUNNING state during execution
        $runner = $this->createMock(RunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (Job $job) use ($jobId) {
                // Verify RUNNING state inside the runner (while worker is working)
                $this->assertFileExists($this->storage . "/queue/running/{$jobId}.json");
                $this->assertFileDoesNotExist($this->storage . "/queue/pending/{$jobId}.json");
                
                // Return success report
                $report = new ExecutionReport($jobId, true, 0, 10, []);
                return [$report, 'stdout output', 'stderr output'];
            });

        // 4. Run Worker
        $worker = new Worker($queue, $runner);
        $processed = $worker->workOnce();

        $this->assertTrue($processed, 'Worker should have processed the job');

        // 5. Verify DONE state
        $this->assertFileExists($this->storage . "/queue/done/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/running/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/pending/{$jobId}.json");
        
        // Verify result files
        $this->assertFileExists($this->storage . "/results/{$jobId}/result.json");
        $this->assertFileExists($this->storage . "/results/{$jobId}/stdout.log");
        
        $result = json_decode(file_get_contents($this->storage . "/results/{$jobId}/result.json"), true);
        $this->assertSame('done', $result['status']);
    }

    public function testJobLifecycleFailure(): void
    {
        // 1. Setup components
        $queue = new FileJobQueue($this->storage);
        $jobId = 'lifecycle-job-fail';
        
        // 2. Enqueue Job (PENDING)
        $job = new Job($jobId, 'MyClass', [], [], time() * 1000);
        $queue->enqueue($job);

        // Verify PENDING state
        $this->assertFileExists($this->storage . "/queue/pending/{$jobId}.json");

        // 3. Prepare Runner Mock to verify RUNNING state then fail
        $runner = $this->createMock(RunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (Job $job) use ($jobId) {
                // Verify RUNNING state
                $this->assertFileExists($this->storage . "/queue/running/{$jobId}.json");
                
                // Return failed report (e.g., exception caught inside runner or explicitly failed)
                $report = new ExecutionReport($jobId, false, 0, 10, ['method' => ['ok' => false]]);
                return [$report, 'stdout output', 'error occurred'];
            });

        // 4. Run Worker
        $worker = new Worker($queue, $runner);
        $processed = $worker->workOnce();

        $this->assertTrue($processed, 'Worker should have processed the job');

        // 5. Verify FAILED state
        $this->assertFileExists($this->storage . "/queue/failed/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/running/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/done/{$jobId}.json");
        
        // Verify result files
        $this->assertFileExists($this->storage . "/results/{$jobId}/result.json");
        $this->assertFileExists($this->storage . "/results/{$jobId}/stderr.log");
        
        $result = json_decode(file_get_contents($this->storage . "/results/{$jobId}/result.json"), true);
        $this->assertSame('failed', $result['status']);
    }

    public function testJobLifecycleException(): void
    {
        // 1. Setup components
        $queue = new FileJobQueue($this->storage);
        $jobId = 'lifecycle-job-exception';
        
        // 2. Enqueue Job
        $job = new Job($jobId, 'MyClass', [], [], time() * 1000);
        $queue->enqueue($job);

        // 3. Prepare Runner Mock to throw Exception
        $runner = $this->createMock(RunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (Job $job) use ($jobId) {
                // Verify RUNNING state
                $this->assertFileExists($this->storage . "/queue/running/{$jobId}.json");
                
                throw new \RuntimeException("Catastrophic failure");
            });

        // 4. Run Worker
        $worker = new Worker($queue, $runner);
        $processed = $worker->workOnce();

        $this->assertTrue($processed);

        // 5. Verify FAILED state
        $this->assertFileExists($this->storage . "/queue/failed/{$jobId}.json");
        $this->assertFileDoesNotExist($this->storage . "/queue/running/{$jobId}.json");
        
        $result = json_decode(file_get_contents($this->storage . "/results/{$jobId}/result.json"), true);
        $this->assertSame('failed', $result['status']);
        $this->assertSame('Catastrophic failure', $result['error']);
    }
}
