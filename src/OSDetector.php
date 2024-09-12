<?php

namespace MithrilExecutor;

class OSDetector
{
    public function getOS(): string
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
            ? 'Windows'
            : 'Unix';
    }
}
