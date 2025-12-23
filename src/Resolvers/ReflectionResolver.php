<?php
declare(strict_types=1);

namespace MithrilExecutor\Resolvers;

use MithrilExecutor\Contracts\ResolverInterface;

final class ReflectionResolver implements ResolverInterface
{
    public function resolve(string $className, array $constructorArgs = []): object
    {
        if (!class_exists($className)) {
            throw new \RuntimeException("Class not found: {$className}. Did you forget to include --bootstrap?");
        }

        $ref = new \ReflectionClass($className);

        // Se não tiver construtor, ok.
        return $ref->newInstanceArgs($constructorArgs);
    }
}
