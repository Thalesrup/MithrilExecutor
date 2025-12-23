<?php
declare(strict_types=1);

namespace MithrilExecutor\Tests\Fixtures;

class SumTask
{
    public function __construct(
        private readonly int $base
    ) {}

    public function add(int $a, int $b): int
    {
        $sum = $this->base + $a + $b;
        echo "Calculating {$this->base} + {$a} + {$b} = {$sum}";
        return $sum;
    }
}
