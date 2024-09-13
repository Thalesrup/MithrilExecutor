# MithrilExecutor

**MithrilExecutor** é uma library do PHP que facilita a execução de tarefas em segundo plano. Ela permite rodar arquivos PHP ou instanciar classes, executar métodos em uma ordem definida e capturar logs e retornos dos métodos passados. Com a **MithrilExecutor**, você pode agendar a execução de tarefas e processá-las de forma assíncrona, ideal para operações longas ou intensivas.

## Instalação

Você pode instalar o **MithrilExecutor** via [Composer](https://getcomposer.org/). No seu terminal, rode o seguinte comando:

```bash
composer require ereborcodeforge/mithrilexecutor
```

##Exemplo: passando instancia como referência

```bash
use MithrilExecutor\BackgroundExecutor;

$this->backgroundInstance = new BackgroundExecutor();
$outputResult = $this->backgroundInstance
    ->withConstruct(TestClass::class)  // Instancia a classe a ser executada
    ->addMethod('clearLog')            // Adiciona o método 'clearLog' para execução
    ->addMethod('fetchDataAndLog')     // Adiciona o método 'fetchDataAndLog' para execução
    ->addMethod('getLog')              // Adiciona o método 'getLog' para execução
    ->runNow()                         // Executa os métodos em segundo plano
    ->getOutPuts();                    // Retorna os resultados de saída

);
```

<br>

##Exemplo: passando arquivo como referencia

```bash
use MithrilExecutor\BackgroundExecutor;

$this->backgroundInstance = new BackgroundExecutor();
$outputResult = $this->backgroundInstance
    ->withFile('/path/to/script.php', 'CllassName') // Define o arquivo PHP a ser executado
    ->addMethod('clearLog')            // Adiciona o método 'clearLog' para execução
    ->addMethod('fetchDataAndLog')     // Adiciona o método 'fetchDataAndLog' para execução
    ->addMethod('getLog')              // Adiciona o método 'getLog' para execução
    ->runNow()                         // Executa os métodos em segundo plano
    ->getOutPuts();                    // Retorna os resultados de saída


```
