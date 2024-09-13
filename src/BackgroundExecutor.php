<?php

namespace MithrilExecutor;

class BackgroundExecutor implements ExecutorInterface
{
    private ProcessManager $processManager;
    private Logger $logger;
    private FileHandler $fileHandler;
    private ScriptTemplateGenerator $templateGenerator;
    private string $instanceClass;
    private array $instanceArgs = [];
    private array $methods = [];
    private string $pathFile = '';

    public function __construct() 
    {
        $this->processManager = new ProcessManager(new OSDetector());
        $this->logger = new Logger();
        $this->fileHandler = new FileHandler();
        $this->templateGenerator = new ScriptTemplateGenerator();
    }

    public function withConstruct(string $instanceClass, array $args = []): self
    {
        $this->instanceClass = $instanceClass;
        $this->instanceArgs = $args;
        return $this;
    }

    public function withFile(string $file, string $className): self
    {
        $this->pathFile = $file;
        $this->instanceClass = $className;
        return $this;
    }

    public function addMethod(string $methodName, array $args = []): self
    {
        $this->methods[] = ['method' => $methodName, 'args' => $args];
        return $this;
    }

    public function runNow(): self
    {
        $tempFile = $this->fileHandler->createTempFile();

        $phpTemplate = $this->handleInstance();

        $this->fileHandler->writeToFile($tempFile, $phpTemplate);
        $this->processManager->startProcess("php $tempFile");
        $this->logger->writeLog("Process started: {$this->processManager->getPID()}");
    
        return $this;
    }

    public function killProcess(): void
    {
        $this->processManager->killProcess();
    }

    public function hasProcessForPID(): bool
    {
        return $this->processManager->hasProcessForPID();
    }

    public function getLogs(): array
    {
        return $this->logger->getLogs();
    }

    public function getOutPuts(): array
    {
        return $this->logger->getOutPuts();
    }

    public function getPID(): string
    {
        return $this->processManager->getPID();
    }

    public function unlinkFileLogs():void
    {
        $this->logger->unlinAllFilePath();
    }

    private function handleInstance(): string
    {
        return $this->pathFile 
            ? $this->generateFileInstance() 
            : $this->generateSerializedInstance();
    }

    private function generateSerializedInstance(): string
    {
        return $this->templateGenerator->buildPhpTemplate(
            $this->instanceClass,
            $this->instanceArgs,
            $this->methods,
            $this->logger->getLogPath(),
            $this->logger->getErrorLogPath(),
            $this->logger->getSerializeFilePath()
        );
    }

    private function generateFileInstance(): string
    {
        return $this->templateGenerator->buildPhpTemplateWithFile(
            $this->instanceClass,
            $this->instanceArgs,
            $this->methods,
            $this->pathFile,
            $this->logger->getLogPath(),
            $this->logger->getErrorLogPath(),
            $this->logger->getSerializeFilePath()
        );
    }
}
