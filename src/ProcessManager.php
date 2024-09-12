<?php

namespace MithrilExecutor;

class ProcessManager
{
    private string $pid;
    private string $os;

    public function __construct(OSDetector $osDetector)
    {
        $this->os = $osDetector->getOS();
    }

    public function startProcess(string $command): void
    {
        ('Windows' === $this->os)
           ? $this->startWindowsProcess($command)
           : $this->startUnixProcess($command);
    }

    private function startWindowsProcess(string $command): void
    {
        $formattedCommand = sprintf("start /B %s", $command);
        pclose(popen($formattedCommand, 'r'));
        $tasklist = shell_exec('tasklist /FI "IMAGENAME eq php.exe" /FO LIST');
        preg_match_all('/(?:Identificação pessoal|PID):?\s*(\d+)/i', $tasklist, $matches);
        $this->pid = end($matches[1]) ?: '';
    }

    private function startUnixProcess(string $command): void
    {
        $formattedCommand = sprintf("%s > /dev/null 2>&1 & echo $!", $command);
        $this->pid = trim(shell_exec($formattedCommand));
    }

    public function killProcess(): void
    {
        $command = ($this->os === 'Windows')
            ? "taskkill /F /PID {$this->pid}"
            : "kill -9 {$this->pid}";
        
        shell_exec($command);
    }

    public function hasProcessForPID(): bool
    {
        $command = ($this->os === 'Windows') 
            ? "tasklist /FI \"PID eq {$this->pid}\""
            : "ps -p {$this->pid}";

        $output = shell_exec($command);
        return strpos($output, (string) $this->pid) !== false;
    }

    public function getPID(): string
    {
        return $this->pid;
    }
}
