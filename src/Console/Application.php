<?php

declare(strict_types=1);

namespace Eymen\Console;

use Eymen\Console\Commands\ConfigCacheCommand;
use Eymen\Console\Commands\MakeCommandCommand;
use Eymen\Console\Commands\MakeControllerCommand;
use Eymen\Console\Commands\MakeEventCommand;
use Eymen\Console\Commands\MakeJobCommand;
use Eymen\Console\Commands\MakeMiddlewareCommand;
use Eymen\Console\Commands\MakeModelCommand;
use Eymen\Console\Commands\MigrateCommand;
use Eymen\Console\Commands\QueueWorkCommand;
use Eymen\Console\Commands\RouteCacheCommand;
use Eymen\Console\Commands\RouteListCommand;
use Eymen\Console\Commands\ServeCommand;
use Eymen\Console\Commands\ViewCacheCommand;
use Eymen\Foundation\Application as FoundationApp;

/**
 * CLI Application.
 *
 * Manages command registration, argv parsing, command dispatching,
 * and help display for the 3ymen CLI tool.
 */
class Application
{
    /** @var array<string, Command> Registered commands indexed by name */
    private array $commands = [];

    /** @var string CLI application name */
    private string $name = '3ymen';

    /** @var string CLI application version */
    private string $version = '1.0.0';

    /** @var FoundationApp The foundation application */
    private FoundationApp $app;

    public function __construct(FoundationApp $app)
    {
        $this->app = $app;

        $this->registerBuiltInCommands();
        $this->loadConsoleRoutes();
    }

    /**
     * Register a command.
     *
     * @param string|Command $command Command class name or instance
     */
    public function register(string|Command $command): void
    {
        if (is_string($command)) {
            $command = $this->app->make($command);
        }

        if (!$command instanceof Command) {
            throw new \InvalidArgumentException(
                'Command must extend ' . Command::class
            );
        }

        $this->commands[$command->getName()] = $command;
    }

    /**
     * Run the CLI application.
     *
     * Parses argv, finds the matching command, and executes it.
     *
     * @param list<string> $argv The raw command-line arguments
     * @return int Exit code (0 = success)
     */
    public function run(array $argv): int
    {
        $parsed = $this->parseArgv($argv);
        $commandName = $parsed['command'];
        $arguments = $parsed['arguments'];
        $options = $parsed['options'];

        // No command or --help flag
        if ($commandName === '' || isset($options['help'])) {
            if ($commandName !== '' && isset($this->commands[$commandName])) {
                $this->showCommandHelp($this->commands[$commandName]);
            } else {
                $this->showHelp();
            }
            return 0;
        }

        // --version flag
        if (isset($options['version'])) {
            $this->showVersion();
            return 0;
        }

        // Find and execute command
        $command = $this->getCommand($commandName);

        if ($command === null) {
            $this->writeError("Command \"{$commandName}\" is not defined.");

            // Suggest similar commands
            $suggestions = $this->suggestCommands($commandName);
            if ($suggestions !== []) {
                $this->writeLine('');
                $this->writeLine('Did you mean one of these?');
                foreach ($suggestions as $suggestion) {
                    $this->writeLine("  \033[32m{$suggestion}\033[0m");
                }
            }

            return 1;
        }

        // Map positional args to named arguments
        $namedArgs = $this->mapArguments($command, $arguments);

        $command->setInput($namedArgs, $options);

        try {
            // Boot the application before running commands
            $this->app->boot();

            return $command->handle();
        } catch (\Throwable $e) {
            $this->writeError($e->getMessage());

            if ($this->app->isDebug()) {
                $this->writeLine('');
                $this->writeLine($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Get a command by name.
     */
    public function getCommand(string $name): ?Command
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * Get all registered commands.
     *
     * @return array<string, Command>
     */
    public function getAllCommands(): array
    {
        return $this->commands;
    }

    /**
     * Parse raw argv into command name, arguments, and options.
     *
     * @param list<string> $argv The raw command-line arguments
     * @return array{command: string, arguments: list<string>, options: array<string, mixed>}
     */
    protected function parseArgv(array $argv): array
    {
        // Remove the script name (argv[0])
        array_shift($argv);

        $command = '';
        $arguments = [];
        $options = [];

        foreach ($argv as $arg) {
            // Long option: --name=value or --name or --flag
            if (str_starts_with($arg, '--')) {
                $option = substr($arg, 2);

                if ($option === '') {
                    continue;
                }

                $equalsPos = strpos($option, '=');

                if ($equalsPos !== false) {
                    $key = substr($option, 0, $equalsPos);
                    $value = substr($option, $equalsPos + 1);
                    $options[$key] = $value;
                } else {
                    $options[$option] = true;
                }
                continue;
            }

            // Short option: -v, -h
            if (str_starts_with($arg, '-') && strlen($arg) > 1) {
                $shortFlags = substr($arg, 1);

                // Handle combined short flags: -vvv
                for ($i = 0; $i < strlen($shortFlags); $i++) {
                    $flag = $shortFlags[$i];
                    $options[$this->expandShortOption($flag)] = true;
                }
                continue;
            }

            // First non-option argument is the command name
            if ($command === '') {
                $command = $arg;
            } else {
                $arguments[] = $arg;
            }
        }

        return [
            'command' => $command,
            'arguments' => $arguments,
            'options' => $options,
        ];
    }

    /**
     * Show the main help listing all available commands.
     */
    protected function showHelp(): void
    {
        $this->writeLine('');
        $this->writeLine("\033[33m{$this->name}\033[0m version \033[32m{$this->version}\033[0m");
        $this->writeLine('');
        $this->writeLine("\033[33mUsage:\033[0m");
        $this->writeLine('  command [options] [arguments]');
        $this->writeLine('');
        $this->writeLine("\033[33mAvailable commands:\033[0m");

        // Group commands by namespace
        $groups = ['_default' => []];

        foreach ($this->commands as $name => $command) {
            $parts = explode(':', $name, 2);

            if (count($parts) === 2) {
                $groups[$parts[0]][] = $command;
            } else {
                $groups['_default'][] = $command;
            }
        }

        // Calculate max name length for alignment
        $maxLen = 0;
        foreach ($this->commands as $name => $command) {
            $maxLen = max($maxLen, strlen($name));
        }

        // Print ungrouped commands first
        foreach ($groups['_default'] as $command) {
            $this->writeLine(sprintf(
                "  \033[32m%-{$maxLen}s\033[0m  %s",
                $command->getName(),
                $command->getDescription(),
            ));
        }

        unset($groups['_default']);

        // Print grouped commands
        foreach ($groups as $group => $commands) {
            $this->writeLine('');
            $this->writeLine(" \033[33m{$group}\033[0m");

            foreach ($commands as $command) {
                $this->writeLine(sprintf(
                    "  \033[32m%-{$maxLen}s\033[0m  %s",
                    $command->getName(),
                    $command->getDescription(),
                ));
            }
        }

        $this->writeLine('');
    }

    /**
     * Show help for a specific command.
     */
    protected function showCommandHelp(Command $command): void
    {
        $this->writeLine('');
        $this->writeLine("\033[33mDescription:\033[0m");
        $this->writeLine("  {$command->getDescription()}");
        $this->writeLine('');

        // Usage line
        $usage = "  php 3ymen {$command->getName()}";

        $args = $command->getArguments();
        foreach ($args as $name => $def) {
            $required = $def['required'] ?? false;
            $usage .= $required ? " <{$name}>" : " [{$name}]";
        }

        $opts = $command->getOptions();
        if ($opts !== []) {
            $usage .= ' [options]';
        }

        $this->writeLine("\033[33mUsage:\033[0m");
        $this->writeLine($usage);

        // Arguments
        if ($args !== []) {
            $this->writeLine('');
            $this->writeLine("\033[33mArguments:\033[0m");

            $maxLen = max(array_map('strlen', array_keys($args)));

            foreach ($args as $name => $def) {
                $description = $def['description'] ?? '';
                $default = isset($def['default']) ? " [default: {$def['default']}]" : '';
                $this->writeLine(sprintf(
                    "  \033[32m%-{$maxLen}s\033[0m  %s%s",
                    $name,
                    $description,
                    $default,
                ));
            }
        }

        // Options
        if ($opts !== []) {
            $this->writeLine('');
            $this->writeLine("\033[33mOptions:\033[0m");

            $maxLen = 0;
            foreach ($opts as $name => $def) {
                $display = "--{$name}";
                if (isset($def['shortcut'])) {
                    $display = "-{$def['shortcut']}, {$display}";
                }
                $maxLen = max($maxLen, strlen($display));
            }

            foreach ($opts as $name => $def) {
                $display = "--{$name}";
                if (isset($def['shortcut'])) {
                    $display = "-{$def['shortcut']}, {$display}";
                }

                $description = $def['description'] ?? '';
                $default = isset($def['default']) ? " [default: {$def['default']}]" : '';

                $this->writeLine(sprintf(
                    "  \033[32m%-{$maxLen}s\033[0m  %s%s",
                    $display,
                    $description,
                    $default,
                ));
            }
        }

        $this->writeLine('');
    }

    /**
     * Register built-in framework commands.
     */
    private function registerBuiltInCommands(): void
    {
        $this->register(new ServeCommand());
        $this->register(new RouteListCommand());
        $this->register(new RouteCacheCommand());
        $this->register(new ConfigCacheCommand());
        $this->register(new ViewCacheCommand());
        $this->register(new MigrateCommand());
        $this->register(new MakeControllerCommand());
        $this->register(new MakeModelCommand());
        $this->register(new MakeMiddlewareCommand());
        $this->register(new MakeCommandCommand());
        $this->register(new MakeEventCommand());
        $this->register(new MakeJobCommand());
        $this->register(new QueueWorkCommand());
    }

    /**
     * Load console routes/commands from routes/console.php.
     */
    private function loadConsoleRoutes(): void
    {
        $consoleFile = $this->app->basePath('routes/console.php');

        if (is_file($consoleFile)) {
            require $consoleFile;
        }

        // Load app-level commands from app/Console/Commands/
        $commandsDir = $this->app->basePath('app/Console/Commands');

        if (is_dir($commandsDir)) {
            foreach (glob($commandsDir . '/*.php') ?: [] as $file) {
                $className = 'App\\Console\\Commands\\' . basename($file, '.php');

                if (class_exists($className)) {
                    $this->register($className);
                }
            }
        }
    }

    /**
     * Expand a single-character option to its long name.
     */
    private function expandShortOption(string $short): string
    {
        return match ($short) {
            'h' => 'help',
            'v' => 'verbose',
            'V' => 'version',
            'q' => 'quiet',
            'n' => 'no-interaction',
            default => $short,
        };
    }

    /**
     * Map positional arguments to named arguments based on command definition.
     *
     * @param list<string> $positional
     * @return array<string, string>
     */
    private function mapArguments(Command $command, array $positional): array
    {
        $named = [];
        $definitions = $command->getArguments();
        $keys = array_keys($definitions);

        foreach ($positional as $i => $value) {
            if (isset($keys[$i])) {
                $named[$keys[$i]] = $value;
            }
        }

        return $named;
    }

    /**
     * Suggest similar command names (Levenshtein distance).
     *
     * @return list<string>
     */
    private function suggestCommands(string $input): array
    {
        $suggestions = [];

        foreach (array_keys($this->commands) as $name) {
            $distance = levenshtein($input, $name);

            if ($distance <= 3) {
                $suggestions[$name] = $distance;
            }
        }

        asort($suggestions);

        return array_slice(array_keys($suggestions), 0, 3);
    }

    /**
     * Show the version information.
     */
    private function showVersion(): void
    {
        $this->writeLine("\033[33m{$this->name}\033[0m version \033[32m{$this->version}\033[0m");
    }

    /**
     * Write a line to stdout.
     */
    private function writeLine(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    /**
     * Write an error message to stderr.
     */
    private function writeError(string $message): void
    {
        fwrite(STDERR, "\033[31m  ERROR  {$message}\033[0m" . PHP_EOL);
    }
}
