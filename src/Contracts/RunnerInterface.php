<?php
declare(strict_types=1);

namespace MithrilExecutor\Contracts;

use MithrilExecutor\ValueObjects\Job;

interface RunnerInterface
{
    /**
     * @return array{0:JobResult,1:string,2:string}
     */
    public function run(Job $job): array;
}
