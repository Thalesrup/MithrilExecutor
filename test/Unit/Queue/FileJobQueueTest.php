<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Unit\Queue;

use MithrilExecutor\Queue\FileJobQueue;
use MithrilExecutor\ValueObjects\Job;
use MithrilExecutor\ValueObjects\ExecutionReport;
use PHPUnit\Framework\TestCase;

class FileJobQueueTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/mithril_test_' . uniqid();
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

    public function testEnqueueAndClaim(): void
    {
        $queue = new FileJobQueue($this->storage);
        
        $job = new Job('job-1', 'MyClass', [], [], 12345);
        $id = $queue->enqueue($job);

        $this->assertSame('job-1', $id);
        $this->assertFileExists($this->storage . '/queue/pending/job-1.json');

        $claimed = $queue->claimNext();
        $this->assertNotNull($claimed);
        $this->assertSame('job-1', $claimed->id);
        $this->assertFileExists($this->storage . '/queue/running/job-1.json');
        $this->assertFileDoesNotExist($this->storage . '/queue/pending/job-1.json');
    }

    public function testClaimEmptyQueue(): void
    {
        $queue = new FileJobQueue($this->storage);
        $this->assertNull($queue->claimNext());
    }

    public function testMarkDone(): void
    {
        $queue = new FileJobQueue($this->storage);
        $job = new Job('job-1', 'MyClass', [], [], 12345);
        $queue->enqueue($job);
        $queue->claimNext();

        $report = new ExecutionReport('job-1', true, 1000, 2000, []);
        $queue->markDone($job, $report, "stdout content", "stderr content");

        $this->assertFileExists($this->storage . '/queue/done/job-1.json');
        $this->assertFileExists($this->storage . '/results/job-1/result.json');
        $this->assertFileExists($this->storage . '/results/job-1/stdout.log');
        $this->assertFileExists($this->storage . '/results/job-1/stderr.log');
        
        $this->assertSame("stdout content", file_get_contents($this->storage . '/results/job-1/stdout.log'));
        
        $result = json_decode(file_get_contents($this->storage . '/results/job-1/result.json'), true);
        $this->assertSame('done', $result['status']);
        $this->assertTrue($result['ok']);
    }

    public function testMarkFailed(): void
    {
        $queue = new FileJobQueue($this->storage);
        $job = new Job('job-1', 'MyClass', [], [], 12345);
        $queue->enqueue($job);
        $queue->claimNext();

        $queue->markFailed($job, new \Exception("Oops"), "out", "err");

        $this->assertFileExists($this->storage . '/queue/failed/job-1.json');
        $this->assertFileExists($this->storage . '/results/job-1/result.json');
        $this->assertFileExists($this->storage . '/results/job-1/stderr.log');
        
        $result = json_decode(file_get_contents($this->storage . '/results/job-1/result.json'), true);
        $this->assertSame('failed', $result['status']);
        $this->assertSame('Oops', $result['error']);
        $this->assertSame('err', file_get_contents($this->storage . '/results/job-1/stderr.log'));
    }
}
