# MithrilExecutor

Simple, dependency-free PHP background job runner using filesystem queues.

## 🚀 Quick Start

### 1. Create a Task Class
Any PHP class can be a job. No interface required.
```php
// src/Tasks/EmailTask.php
namespace App\Tasks;

class EmailTask {
    public function __construct(private string $apiKey) {}

    public function send(string $to, string $subject): void {
        // Send email logic...
        echo "Email sent to $to";
    }
}
```

### 2. Enqueue a Job (Programmatic)
Use this in your application (Controller, Service, etc) to dispatch jobs.
```php
use MithrilExecutor\Queue\FileJobQueue;
use MithrilExecutor\ValueObjects\Job;
use App\Tasks\EmailTask;

// Setup
$queue = new FileJobQueue(__DIR__ . '/storage');

// Create Job
$job = new Job(
    id: uniqid('job-'),
    className: EmailTask::class,
    constructorArgs: ['my-api-key'], // Passed to __construct
    calls: [
        [
            'method' => 'send', 
            'args' => ['user@example.com', 'Welcome!'] // Passed to method
        ]
    ]
);

// Dispatch
$queue->enqueue($job);
```

### 3. Run the Worker (Terminal)
Start the supervisor to process jobs.
```bash
php bin/mithril daemon --bootstrap=example/bootstrap.php --concurrency=4 --memory=128M
```

---

## 🛠 CLI Reference & Flags

### Global Flags (Available for most commands)
| Flag | Description |
|------|-------------|
| `--bootstrap` | Path to a PHP file that returns a `ResolverInterface`. Used to load your application's autoloader or DI container. **Optional** if your jobs are already autoloaded by Composer in the project root. |
| `--storage` | Custom path to the storage directory (defaults to `storage/`). |

### `daemon`
Starts the worker loop (supervisor). Recommended for production.

| Flag | Default | Description | Example |
|------|---------|-------------|---------|
| `--concurrency` | `1` | Number of parallel worker processes to spawn. | `--concurrency=5` |
| `--memory` | `null` | Memory limit **per process** (passed to `ini_set` or PHP CLI). | `--memory=256M` |
| `--sleep` | `250` | Sleep time (ms) when queue is empty to reduce CPU usage. | `--sleep=1000` |
| `--bootstrap` | `null` | Autoloader/Resolver file (see Global Flags). | `--bootstrap=autoload.php` |

**Example:**
```bash
# Runs 5 workers, each limited to 128MB RAM, loading classes from bootstrap.php
php bin/mithril daemon --concurrency=5 --memory=128M --bootstrap=src/bootstrap.php
```

### `list`
Lists jobs in the queue.

| Flag | Options | Description | Example |
|------|---------|-------------|---------|
| `--status` | `pending`, `running`, `done`, `failed` | Filter jobs by status. Default shows all. | `--status=failed` |
| `--format` | `table` (default), `json`, `csv` | Output format. JSON/CSV are good for scripts. | `--format=json` |
| `--output` | `null` | Write output to a file instead of the screen. | `--output=report.csv` |

**Example:**
```bash
# Export all failed jobs to a CSV file
php bin/mithril list --status=failed --format=csv --output=failed_jobs.csv
```

### `work`
Processes a **single** job and exits. Useful for cron jobs or one-off tasks.

| Flag | Description |
|------|-------------|
| `--quiet` | Suppress "No job found" and "Running worker" logs. Useful for cron to avoid spamming emails. |
| `--memory` | Set memory limit for this specific run. |

**Example:**
```bash
php bin/mithril work --memory=512M --quiet --bootstrap=autoload.php
```

### `run`
Executes a specific job file directly (bypassing the queue logic). Useful for debugging a failed job file manually.

| Flag | Description |
|------|-------------|
| `--file` | **Required**. Absolute path to the `.json` job file to execute. |

**Example:**
```bash
php bin/mithril run --file=/path/to/storage/queue/failed/job-123.json --bootstrap=autoload.php
```

### `enqueue`
Enqueue a job via CLI (useful for testing).

| Flag | Description |
|------|-------------|
| `--class` | Full class name of the job. |
| `--ctor` | JSON array of arguments for the constructor. |
| `--calls` | JSON array of method calls: `[{"method":"name","args":[...]}]`. |

**Example:**
```bash
php bin/mithril enqueue \
  --class="App\Tasks\EmailTask" \
  --ctor='["api-key"]' \
  --calls='[{"method":"send","args":["thales@example.com","Hello"]}]'
```

---

## 📂 Directory Structure

- `bin/mithril`: The CLI executable.
- `storage/queue`: Where pending/running/done jobs are stored as JSON.
- `src/`: The library source code.
- `example/`: Demo files.
