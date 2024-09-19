# MithrilExecutor

**MithrilExecutor** is a PHP library that facilitates the execution of background tasks. It allows you to run PHP files or instantiate classes, execute methods in a defined order, and capture logs and return values from the referenced methods. With **MithrilExecutor**, you can schedule task execution and process them asynchronously, making it ideal for long-running or intensive operations.

## Installation

You can install **MithrilExecutor** via [Composer](https://getcomposer.org/). Run the following command in your terminal:

<br>

```bash
composer require ereborcodeforge/mithrilexecutor


##Example: passing an instance as a reference

```bash
use MithrilExecutor\BackgroundExecutor;

$backgroundInstance = new BackgroundExecutor();
$outputResult = $backgroundInstance
    ->withConstruct(TestClass::class)  // Instantiates the class to be executed
    ->addMethod('clearLog')            // Adds the 'clearLog' method for execution
    ->addMethod('fetchDataAndLog')     // Adds the 'fetchDataAndLog' method for execution
    ->addMethod('getLog')              // Adds the 'getLog' method for execution
    ->runNow()                         // Executes the methods in the background
    ->getOutPuts();                    // Returns the output results

```

<br>

Example: passing a file as a reference

```bash
use MithrilExecutor\BackgroundExecutor;

$backgroundInstance = new BackgroundExecutor();
$outputResult = $backgroundInstance
    ->withFile('/path/to/script.php', 'ClassName') // Defines the PHP file to be executed
    ->addMethod('clearLog')            // Adds the 'clearLog' method for execution
    ->addMethod('fetchDataAndLog')     // Adds the 'fetchDataAndLog' method for execution
    ->addMethod('getLog')              // Adds the 'getLog' method for execution
    ->runNow()                         // Executes the methods in the background
    ->getOutPuts();                    // Returns the output results

```
