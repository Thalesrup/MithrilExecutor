<?php

namespace MithrilExecutor;

interface ExecutorInterface
{
    public function runNow(): self;
    public function killProcess(): void;
    public function hasProcessForPID(): bool;
}
