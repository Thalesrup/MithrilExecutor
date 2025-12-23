<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Unit\Resolvers;

use MithrilExecutor\Resolvers\BootstrapResolverLoader;
use MithrilExecutor\Resolvers\ReflectionResolver;
use MithrilExecutor\Contracts\ResolverInterface;
use PHPUnit\Framework\TestCase;

class BootstrapResolverLoaderTest extends TestCase
{
    private string $bootstrapFile;

    protected function setUp(): void
    {
        $this->bootstrapFile = sys_get_temp_dir() . '/bootstrap_test_' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->bootstrapFile)) {
            unlink($this->bootstrapFile);
        }
    }

    public function testLoadNullReturnsReflectionResolver(): void
    {
        $resolver = BootstrapResolverLoader::load(null);
        $this->assertInstanceOf(ReflectionResolver::class, $resolver);
    }

    public function testLoadValidBootstrap(): void
    {
        $content = <<<'PHP'
<?php
use MithrilExecutor\Contracts\ResolverInterface;

return new class implements ResolverInterface {
    public function resolve(string $className, array $args = []): object {
        return new \stdClass();
    }
};
PHP;
        file_put_contents($this->bootstrapFile, $content);

        $resolver = BootstrapResolverLoader::load($this->bootstrapFile);
        $this->assertInstanceOf(ResolverInterface::class, $resolver);
        $this->assertNotInstanceOf(ReflectionResolver::class, $resolver);
    }

    public function testLoadInvalidFileThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        BootstrapResolverLoader::load('/non/existent/path.php');
    }

    public function testLoadInvalidReturnThrowsException(): void
    {
        file_put_contents($this->bootstrapFile, '<?php return "not a resolver";');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Bootstrap must return ResolverInterface');
        BootstrapResolverLoader::load($this->bootstrapFile);
    }
}
