<?php
declare(strict_types=1);

namespace Test\MithrilExecutor\Integration;

use PHPUnit\Framework\TestCase;

class ExampleUsageTest extends TestCase
{
    private string $exampleDir;
    private string $bin;

    protected function setUp(): void
    {
        $this->exampleDir = realpath(__DIR__ . '/../../example');
        $this->bin = realpath(__DIR__ . '/../../bin/mithril');
        
        // Limpar reports anteriores
        $this->recursiveDelete($this->exampleDir . '/reports');
        $this->recursiveDelete(__DIR__ . '/../../storage/queue');
        $this->recursiveDelete(__DIR__ . '/../../storage/results');
    }

    protected function tearDown(): void
    {
        // Cleanup reports
        $this->recursiveDelete($this->exampleDir . '/reports');
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

    public function testRealWorldReportGeneration(): void
    {
        // 1. Executar o script de enqueue (Simulando a aplicação enviando o job)
        $output = [];
        $returnVar = 0;
        exec("php " . escapeshellarg($this->exampleDir . '/enqueue_job.php'), $output, $returnVar);
        
        $this->assertSame(0, $returnVar, "Enqueue script failed: " . implode("\n", $output));
        $this->assertStringContainsString("Job enqueued successfully!", implode("\n", $output));

        // 2. Executar o worker (Simulando o processamento em background)
        // Usamos --bootstrap para carregar a classe ReportGenerator
        $cmdWork = sprintf(
            'php "%s" work --bootstrap="%s"',
            $this->bin,
            $this->exampleDir . '/bootstrap.php'
        );

        $outputWorker = [];
        $returnWorker = 0;
        exec($cmdWork, $outputWorker, $returnWorker);

        // O worker retorna 0 se processou um job, 1 se não tinha nada
        $this->assertSame(0, $returnWorker, "Worker failed or found no job: " . implode("\n", $outputWorker));

        // 3. Verificar se o relatório foi gerado (Side Effect do Job)
        $reportFile = $this->exampleDir . '/reports/report_financial_2025.csv';
        $this->assertFileExists($reportFile, "Report file was not generated!");
        
        $content = file_get_contents($reportFile);
        $this->assertStringContainsString("ID,Value", $content);
        $this->assertStringContainsString("1,100", $content);
    }
}
