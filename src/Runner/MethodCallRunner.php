<?php
declare(strict_types=1);

namespace MithrilExecutor\Runner;

use MithrilExecutor\Contracts\ResolverInterface;
use MithrilExecutor\Contracts\RunnerInterface;
use MithrilExecutor\ValueObjects\ExecutionReport;
use MithrilExecutor\ValueObjects\Job;

final class MethodCallRunner implements RunnerInterface
{
    public function __construct(
        private readonly ResolverInterface $resolver,
    ) {}

    /**
     * Executa um Job:
     * - resolve a classe (via resolver da aplicação)
     * - chama métodos declarados em `calls` (cada um com args)
     * - captura stdout/stderr (stderr via set_error_handler e output buffering)
     *
     * Retorna: [ExecutionReport, stdout, stderr]
     *
     * @return array{0:ExecutionReport,1:string,2:string}
     */
    public function run(Job $job): array
    {
        $startedAt = (int) floor(microtime(true) * 1000);
        $stderr = '';

        // Captura warnings/notices para "stderr" lógico
        set_error_handler(function(int $severity, string $message, string $file, int $line) use (&$stderr): bool {
            $stderr .= "[PHP {$severity}] {$message} at {$file}:{$line}\n";
            return true; // handled
        });

        $initialLevel = ob_get_level();
        $bufferClosed = false;

        try {
            ob_start();
            $instance = $this->resolver->resolve($job->className, $job->constructorArgs);

            $callReports = [];
            $allOk = true;

            foreach ($job->calls as $call) {
                $method = (string)($call['method'] ?? '');
                $args = (array)($call['args'] ?? []);
                $t0 = (int) floor(microtime(true) * 1000);

                try {
                    if (!method_exists($instance, $method)) {
                        throw new \BadMethodCallException("Method not found: {$job->className}::{$method}()");
                    }

                    // Chamada direta (sem ReflectionMethod):
                    $return = $instance->{$method}(...$args);

                    $t1 = (int) floor(microtime(true) * 1000);
                    $callReports[$method] = [
                        'ok' => true,
                        'return' => $return,
                        'durationMs' => $t1 - $t0,
                    ];
                } catch (\Throwable $e) {
                    $t1 = (int) floor(microtime(true) * 1000);
                    $allOk = false;
                    $callReports[$method] = [
                        'ok' => false,
                        'return' => null,
                        'error' => $e->getMessage(),
                        'durationMs' => $t1 - $t0,
                    ];
                }
            }

            $stdout = (string) ob_get_clean();
            $finishedAt = (int) floor(microtime(true) * 1000);

            $report = new ExecutionReport(
                $job->id,
                $allOk,
                $startedAt,
                $finishedAt,
                $callReports
            );

            return [$report, $stdout, $stderr];
        } finally {
            restore_error_handler();
            
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }
    }
}
