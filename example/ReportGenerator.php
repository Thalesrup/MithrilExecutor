<?php

namespace Example;

class ReportGenerator
{
    public function __construct(
        private string $outputDir
    ) {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }

    public function generate(string $reportType, int $year): array
    {
        echo "Starting {$reportType} report generation for {$year}...\n";
        
        echo "Simulating heavy database access (5 seconds)...\n";
        sleep(5);
        
        // Simula processamento pesado
        for ($i = 0; $i < 5; $i++) {
            echo "Processing chunk {$i}...\n";
            usleep(200000); // 0.2s
        }

        $filename = "report_{$reportType}_{$year}.csv";
        $path = $this->outputDir . DIRECTORY_SEPARATOR . $filename;
        
        file_put_contents($path, "ID,Value\n1,100\n2,200");
        
        echo "Report saved to {$path}\n";

        return [
            'status' => 'generated',
            'file' => $path,
            'size' => filesize($path)
        ];
    }
}
