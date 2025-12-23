<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Unit\Resolvers;

use MithrilExecutor\Resolvers\ReflectionResolver;
use PHPUnit\Framework\TestCase;

class ReflectionResolverTest extends TestCase
{
    public function testResolveSimpleClass(): void
    {
        $resolver = new ReflectionResolver();
        $obj = $resolver->resolve(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $obj);
    }

    public function testResolveWithConstructorArgs(): void
    {
        $resolver = new ReflectionResolver();
        // DateTime takes string in constructor
        $obj = $resolver->resolve(\DateTime::class, ['2023-01-01']);
        $this->assertInstanceOf(\DateTime::class, $obj);
        $this->assertEquals('2023-01-01', $obj->format('Y-m-d'));
    }

    public function testResolveNotFoundClassThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Class not found: NonExistentClass');
        
        $resolver = new ReflectionResolver();
        $resolver->resolve('NonExistentClass');
    }
}
