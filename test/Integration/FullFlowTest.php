<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Integration;

use MithrilExecutor\Tests\Fixtures\SumTask;
use PHPUnit\Framework\TestCase;

class FullFlowTest extends TestCase
{
    private string $storage;
    private string $bin;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/mithril_e2e_' . uniqid();
        mkdir($this->storage);
        mkdir($this->storage . '/queue/pending', 0777, true);
        
        $this->bin = realpath(__DIR__ . '/../../bin/mithril');
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

    public function testCliFlow(): void
    {
        $class = str_replace('\\', '\\\\', SumTask::class); // Escape for shell
        $ctor = json_encode([10]);
        $calls = json_encode([['method' => 'add', 'args' => [5, 5]]]);
        
        // 1. Enqueue
        // We need to escape quotes for Windows shell properly or use simpler args
        // PowerShell escaping is tricky, so let's try to execute it carefully.
        // Actually, PHP exec() on Windows uses cmd.exe syntax usually, but here we are in PowerShell environment?
        // Let's try to construct the command line.
        
        // Use PHP's built-in escapeshellarg is safer usually, but on Windows it can be weird.
        // Let's try constructing the command string manually with double quotes for arguments.
        
        $cmdEnqueue = sprintf(
            'php "%s" enqueue --storage="%s" --class="%s" --ctor=\'%s\' --calls=\'%s\'',
            $this->bin,
            $this->storage,
            SumTask::class,
            $ctor,
            $calls
        );
        
        // On Windows, json inside arguments with quotes is hell.
        // Let's simplify: Use in-process testing for the heavy logic, and CLI testing for simple args if possible.
        // Or, write the JSON to a file and pass it? But enqueue doesn't support file input for args yet (except --file for run).
        
        // Alternative: Use the "run --file" command which takes a full JSON file. This is easier to test via CLI.
        
        $jobId = 'test-job-' . uniqid();
        $jobData = [
            'id' => $jobId,
            'className' => SumTask::class,
            'constructorArgs' => [10],
            'calls' => [
                ['method' => 'add', 'args' => [5, 5]]
            ],
            'createdAtMs' => 123456
        ];
        
        $jobFile = $this->storage . '/job_input.json';
        file_put_contents($jobFile, json_encode($jobData));
        
        $cmdRun = sprintf(
            'php "%s" run --file="%s" --storage="%s"',
            $this->bin,
            $jobFile,
            $this->storage
        );
        
        $output = [];
        $returnVar = 0;
        exec($cmdRun, $output, $returnVar);
        
        $this->assertSame(0, $returnVar, "Command failed: " . implode("\n", $output));
        
        // Parse output (last line should be JSON report)
        $jsonOutput = implode("", $output);
        // Extract JSON part if there is extra noise
        // But run command prints JSON report to stdout.
        
        // Find the JSON block
        $report = json_decode($jsonOutput, true);
        if ($report === null) {
            // Try to find { ... }
            if (preg_match('/\{.*\}/s', $jsonOutput, $matches)) {
                $report = json_decode($matches[0], true);
            }
        }

        $this->assertIsArray($report, "Could not parse JSON report from output: $jsonOutput");
        $this->assertTrue($report['ok']);
        $this->assertSame($jobId, $report['jobId']);
        $this->assertSame(20, $report['calls']['add']['return']); // 10 + 5 + 5
    }

    public function testWorkerProcessFlow(): void
    {
        // This tests the Queue + Worker interaction via CLI
        
        // 1. Manually create a pending job file to avoid shell escaping issues with 'enqueue'
        $jobId = 'job-integration-1';
        $jobData = [
            'id' => $jobId,
            'className' => SumTask::class,
            'constructorArgs' => [100],
            'calls' => [
                ['method' => 'add', 'args' => [1, 1]]
            ],
            'createdAtMs' => time() * 1000
        ];
        
        file_put_contents(
            $this->storage . '/queue/pending/' . $jobId . '.json', 
            json_encode($jobData)
        );
        
        // 2. Run worker once
        $cmdWork = sprintf(
            'php "%s" work --storage="%s"',
            $this->bin,
            $this->storage
        );
        
        $output = [];
        $returnVar = 0;
        exec($cmdWork, $output, $returnVar);
        
        $this->assertSame(0, $returnVar, "Worker failed: " . implode("\n", $output));
        
        // 3. Verify results on disk
        $resultFile = $this->storage . '/results/' . $jobId . '/result.json';
        $this->assertFileExists($resultFile);
        
        $resultData = json_decode(file_get_contents($resultFile), true);
        $this->assertTrue($resultData['ok']);
        $this->assertSame(102, $resultData['calls']['add']['return']); // 100 + 1 + 1
        
        // 4. Verify stdout log
        $stdoutFile = $this->storage . '/results/' . $jobId . '/stdout.log';
        $this->assertFileExists($stdoutFile);
        $this->assertStringContainsString('Calculating 100 + 1 + 1 = 102', file_get_contents($stdoutFile));
    }
}
