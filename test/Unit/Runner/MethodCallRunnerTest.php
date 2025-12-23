<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Unit\Runner;

use MithrilExecutor\Contracts\ResolverInterface;
use MithrilExecutor\Runner\MethodCallRunner;
use MithrilExecutor\ValueObjects\Job;
use PHPUnit\Framework\TestCase;

class MethodCallRunnerTest extends TestCase
{
    public function testRunSuccess(): void
    {
        $target = new class {
            public function echoUpper(string $s): string {
                echo "Processing $s";
                return strtoupper($s);
            }
        };

        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->method('resolve')->willReturn($target);

        $runner = new MethodCallRunner($resolver);

        $job = new Job(
            '1', 
            'TargetClass', 
            [], 
            [['method' => 'echoUpper', 'args' => ['hello']]], 
            0, 
            null
        );

        [$report, $stdout, $stderr] = $runner->run($job);

        $this->assertTrue($report->ok);
        $this->assertSame('Processing hello', $stdout);
        $this->assertSame('HELLO', $report->calls['echoUpper']['return']);
    }

    public function testRunFailure(): void
    {
        $target = new class {
            public function failMe(): void {
                throw new \Exception("Boom");
            }
        };

        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->method('resolve')->willReturn($target);

        $runner = new MethodCallRunner($resolver);

        $job = new Job(
            '1', 
            'TargetClass', 
            [], 
            [['method' => 'failMe', 'args' => []]], 
            0, 
            null
        );

        [$report, $stdout, $stderr] = $runner->run($job);

        $this->assertFalse($report->ok);
        $this->assertSame('Boom', $report->calls['failMe']['error']);
    }

    public function testMethodNotFound(): void
    {
        $target = new class {};

        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->method('resolve')->willReturn($target);

        $runner = new MethodCallRunner($resolver);

        $job = new Job(
            '1', 
            'TargetClass', 
            [], 
            [['method' => 'missing', 'args' => []]], 
            0, 
            null
        );

        [$report, $stdout, $stderr] = $runner->run($job);

        $this->assertFalse($report->ok);
        $this->assertStringContainsString('Method not found', $report->calls['missing']['error']);
    }
}
