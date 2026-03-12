<?php

declare(strict_types=1);

use Eymen\Log\Logger;
use Eymen\Log\LogHandlerInterface;
use Eymen\Log\Handlers\FileHandler;

// Shared log collector
class LogCollector implements LogHandlerInterface
{
    public array $messages = [];

    public function handle(string $level, string $message, array $context = []): void
    {
        $this->messages[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function isHandling(string $level): bool
    {
        return true;
    }
}

test('logger writes to handler', function () {
    $handler = new LogCollector();
    $logger = new Logger();
    $logger->addHandler($handler);
    $logger->info('Test message');

    expect($handler->messages)->toHaveCount(1);
    expect($handler->messages[0]['level'])->toBe('info');
    expect($handler->messages[0]['message'])->toBe('Test message');
});

test('all log levels work', function () {
    $handler = new LogCollector();
    $logger = new Logger();
    $logger->addHandler($handler);

    $logger->emergency('emergency');
    $logger->alert('alert');
    $logger->critical('critical');
    $logger->error('error');
    $logger->warning('warning');
    $logger->notice('notice');
    $logger->info('info');
    $logger->debug('debug');

    $levels = array_column($handler->messages, 'level');
    expect($levels)->toBe(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']);
});

test('minimum level filters messages', function () {
    $handler = new LogCollector();
    $logger = new Logger(Logger::WARNING);
    $logger->addHandler($handler);

    $logger->debug('skip');
    $logger->info('skip');
    $logger->notice('skip');
    $logger->warning('keep');
    $logger->error('keep');
    $logger->critical('keep');

    $levels = array_column($handler->messages, 'level');
    expect($levels)->toBe(['warning', 'error', 'critical']);
});

test('context interpolation replaces placeholders', function () {
    $handler = new LogCollector();
    $logger = new Logger();
    $logger->addHandler($handler);

    $logger->info('User {name} logged in from {ip}', ['name' => 'Eymen', 'ip' => '127.0.0.1']);

    expect($handler->messages[0]['message'])->toBe('User Eymen logged in from 127.0.0.1');
});

test('multiple handlers receive same message', function () {
    $handler1 = new LogCollector();
    $handler2 = new LogCollector();

    $logger = new Logger();
    $logger->addHandler($handler1);
    $logger->addHandler($handler2);
    $logger->info('test');

    expect($handler1->messages)->toHaveCount(1);
    expect($handler2->messages)->toHaveCount(1);
});

test('file handler writes to log file', function () {
    $logDir = sys_get_temp_dir() . '/eymen_log_test_' . uniqid();
    mkdir($logDir, 0755, true);
    $logFile = $logDir . '/' . date('Y-m-d') . '.log';

    $handler = new FileHandler($logFile);
    $logger = new Logger();
    $logger->addHandler($handler);

    $logger->info('File log test');
    $logger->error('Error test');

    expect(file_exists($logFile))->toBeTrue();

    $content = file_get_contents($logFile);
    expect($content)->toContain('File log test');
    expect($content)->toContain('Error test');

    unlink($logFile);
    rmdir($logDir);
});

test('log method accepts level as parameter', function () {
    $handler = new LogCollector();
    $logger = new Logger();
    $logger->addHandler($handler);
    $logger->log('error', 'Custom level log');

    expect($handler->messages[0]['level'])->toBe('error');
    expect($handler->messages[0]['message'])->toBe('Custom level log');
});
