<?php
declare(strict_types=1);

namespace MithrilExecutor\Resolvers;

use MithrilExecutor\Contracts\ResolverInterface;

final class BootstrapResolverLoader
{
    /**
     * Carrega um arquivo PHP que retorna um ResolverInterface.
     *
     * Exemplo do bootstrap:
     * <?php
     * use MithrilExecutor\Contracts\ResolverInterface;
     * return new class implements ResolverInterface { ... };
     */
    public static function load(?string $bootstrapPath): ResolverInterface
    {
        if ($bootstrapPath === null || trim($bootstrapPath) === '') {
            return new ReflectionResolver();
        }

        $bootstrapPath = realpath($bootstrapPath) ?: $bootstrapPath;

        if (!is_file($bootstrapPath)) {
            throw new \RuntimeException("Bootstrap file not found: {$bootstrapPath}");
        }

        $resolver = require $bootstrapPath;

        if (!$resolver instanceof ResolverInterface) {
            $type = is_object($resolver) ? get_class($resolver) : gettype($resolver);
            throw new \RuntimeException("Bootstrap must return ResolverInterface, got: {$type}");
        }

        return $resolver;
    }
}
