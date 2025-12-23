<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/bootstrap.php';

use MithrilExecutor\Queue\FileJobQueue;
use MithrilExecutor\ValueObjects\Job;
use Example\ReportGenerator;

// 1. Configurar a fila (normalmente injetado via Container)
$storagePath = __DIR__ . '/../storage';
$queue = new FileJobQueue($storagePath);

// 2. Definir o Job
// Vamos instanciar ReportGenerator com o diretório de saída
$reportOutputDir = __DIR__ . '/reports';

$job = new Job(
    id: 'report-' . date('Ymd-His'),
    className: ReportGenerator::class,
    constructorArgs: [$reportOutputDir], // Injeção de dependência no construtor
    calls: [
        [
            'method' => 'generate',
            'args' => ['financial', 2025]
        ]
    ],
    createdAtMs: (int) floor(microtime(true) * 1000)
);

// 3. Enfileirar
$jobId = $queue->enqueue($job);

echo "Job enqueued successfully!\n";
echo "ID: {$jobId}\n";
echo "Class: {$job->className}\n";
echo "\nTo process this job, run:\n";
echo "php bin/mithril work --bootstrap=example/bootstrap.php\n";
