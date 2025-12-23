<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Unit\ValueObjects;

use MithrilExecutor\ValueObjects\Job;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testNewIdReturnsString(): void
    {
        $id = Job::newId();
        $this->assertNotEmpty($id);
        $this->assertMatchesRegularExpression('/^\d{14}-[a-f0-9]+$/', $id);
    }

    public function testFromArrayAndToArray(): void
    {
        $data = [
            'id' => '123',
            'className' => 'MyClass',
            'constructorArgs' => ['arg1'],
            'calls' => [
                ['method' => 'run', 'args' => ['a', 'b']]
            ],
            'createdAtMs' => 1000,
            'meta' => 'some-meta'
        ];

        $job = Job::fromArray($data);

        $this->assertSame('123', $job->id);
        $this->assertSame('MyClass', $job->className);
        $this->assertSame(['arg1'], $job->constructorArgs);
        $this->assertSame([['method' => 'run', 'args' => ['a', 'b']]], $job->calls);
        $this->assertSame(1000, $job->createdAtMs);
        $this->assertSame('some-meta', $job->meta);

        $this->assertEquals($data, $job->toArray());
    }

    public function testDefaults(): void
    {
        $job = Job::fromArray([]);
        
        $this->assertNotEmpty($job->id);
        $this->assertSame('', $job->className);
        $this->assertSame([], $job->constructorArgs);
        $this->assertSame([], $job->calls);
        $this->assertNull($job->meta);
    }
}
