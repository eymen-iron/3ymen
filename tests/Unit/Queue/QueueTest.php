<?php

declare(strict_types=1);

use Eymen\Queue\Job;
use Eymen\Queue\Drivers\SyncDriver;

// Test job class
class TestJob extends Job
{
    public static bool $handled = false;
    public static int $handleCount = 0;

    public function __construct(
        public string $message = 'test'
    ) {
    }

    public function handle(): void
    {
        self::$handled = true;
        self::$handleCount++;
    }

    public function failed(\Throwable $exception): void
    {
        // no-op
    }
}

class FailingJob extends Job
{
    public static bool $failedCalled = false;

    public function handle(): void
    {
        throw new \RuntimeException('Job failed intentionally');
    }

    public function failed(\Throwable $exception): void
    {
        self::$failedCalled = true;
    }
}

beforeEach(function () {
    TestJob::$handled = false;
    TestJob::$handleCount = 0;
    FailingJob::$failedCalled = false;
});

// Job base class
test('job has default properties', function () {
    $job = new TestJob();
    expect($job->tries)->toBe(3);
    expect($job->timeout)->toBe(60);
    expect($job->retryAfter)->toBe(60);
    expect($job->queue)->toBeNull();
});

test('job getDisplayName returns class name', function () {
    $job = new TestJob();
    expect($job->getDisplayName())->toBe('TestJob');
});

test('job serialization roundtrip', function () {
    $job = new TestJob('hello');
    $data = $job->jsonSerialize();

    expect($data)->toHaveKey('class');
    expect($data['class'])->toBe(TestJob::class);

    $restored = TestJob::fromArray($data);
    expect($restored)->toBeInstanceOf(TestJob::class);
    expect($restored->message)->toBe('hello');
});

// SyncDriver
test('sync driver executes job immediately', function () {
    $driver = new SyncDriver();
    $job = new TestJob('sync test');

    $driver->push($job);

    expect(TestJob::$handled)->toBeTrue();
    expect(TestJob::$handleCount)->toBe(1);
});

test('sync driver size is always zero', function () {
    $driver = new SyncDriver();
    expect($driver->size())->toBe(0);
});

test('sync driver push returns id', function () {
    $driver = new SyncDriver();
    $id = $driver->push(new TestJob());
    expect($id)->not->toBeNull();
});

test('sync driver later executes immediately too', function () {
    $driver = new SyncDriver();
    $driver->later(60, new TestJob());

    expect(TestJob::$handled)->toBeTrue();
});

test('sync driver clear returns zero', function () {
    $driver = new SyncDriver();
    expect($driver->clear())->toBe(0);
});

test('sync driver pop returns null', function () {
    $driver = new SyncDriver();
    expect($driver->pop())->toBeNull();
});
