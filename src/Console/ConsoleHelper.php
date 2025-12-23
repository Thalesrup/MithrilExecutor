<?php
declare(strict_types=1);

namespace MithrilExecutor\Console;

class ConsoleHelper
{
    private const COLOR_RED = '31';
    private const COLOR_GREEN = '32';
    private const COLOR_YELLOW = '33';
    private const COLOR_CYAN = '36';

    public static function arg(string $name, ?string $default = null): ?string
    {
        global $argv;
        foreach ($argv as $i => $v) {
            if (str_starts_with($v, "--{$name}=")) {
                return substr($v, strlen("--{$name}="));
            }
            if ($v === "--{$name}" && isset($argv[$i+1]) && !str_starts_with($argv[$i+1], '--')) {
                return $argv[$i+1];
            }
        }
        return $default;
    }

    public static function hasFlag(string $name): bool
    {
        global $argv;
        return in_array("--{$name}", $argv, true);
    }

    public static function cmd(): string
    {
        global $argv;
        return $argv[1] ?? 'help';
    }

    public static function println(string $s): void
    {
        fwrite(STDOUT, $s . PHP_EOL);
    }

    public static function eprintln(string $s): void
    {
        fwrite(STDERR, $s . PHP_EOL);
    }

    public static function colorize(string $text, string $colorCode): string
    {
        return "\033[{$colorCode}m{$text}\033[0m";
    }

    public static function info(string $msg): void
    {
        self::println(self::colorize("ℹ {$msg}", self::COLOR_CYAN));
    }

    public static function success(string $msg): void
    {
        self::println(self::colorize("✔ {$msg}", self::COLOR_GREEN));
    }

    public static function warn(string $msg): void
    {
        self::println(self::colorize("⚠ {$msg}", self::COLOR_YELLOW));
    }

    public static function error(string $msg): void
    {
        self::eprintln(self::colorize("✖ {$msg}", self::COLOR_RED));
    }

    public static function banner(): void
    {
        $art = <<<ART
     __  ____ __  __         _ ________                     __            
    /  |/  (_) /_/ /_  _____(_) / ____/  _____  _______  __/ /_____  _____ 
   / /|_/ / / __/ __ \/ ___/ / / __/ | |/_/ _ \/ ___/ / / / __/ __ \/ ___/ 
  / /  / / / /_/ / / / /  / / / /____>  </  __/ /__/ /_/ / /_/ /_/ / /    
 /_/  /_/_/\__/_/ /_/_/  /_/_/_____/_/|_|\___/\___/\__,_/\__/\____/_/
ART;
        self::println("");
        self::println(self::colorize($art, self::COLOR_CYAN));
        self::println(self::colorize("                                      by EreborCodeForge", self::COLOR_GREEN));
        self::println("");
    }
}
