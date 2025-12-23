<?php
declare(strict_types=1);

namespace MithrilExecutor\Contracts;

interface ResolverInterface
{
    /**
     * Resolve uma classe em uma instância.
     * A implementação é responsabilidade da aplicação: container, factory, strategy, etc.
     */
    public function resolve(string $className, array $constructorArgs = []): object;
}
