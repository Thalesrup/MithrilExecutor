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

        if ($resolver instanceof ResolverInterface) {
            return $resolver;
        }

        // Se o arquivo foi incluído mas não retornou um resolver (ex: retornou '1' do require),
        // assumimos que foi apenas para carregar classes/funções e usamos o Resolver padrão.
        return new ReflectionResolver();
    }
}
