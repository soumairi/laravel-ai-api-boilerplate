<?php

declare(strict_types=1);

use App\Infrastructure\Logging\SanitizeLogs;
use Monolog\Level;
use Monolog\LogRecord;

it('recursively redacts sensitive logging context', function (): void {
    $record = new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'test',
        level: Level::Info,
        message: 'safe message',
        context: [
            'email' => 'safe@example.test',
            'password' => 'not-for-logs',
            'nested' => ['authorization' => 'not-for-logs'],
        ],
        extra: ['api_key' => 'not-for-logs'],
    );

    $sanitized = (new SanitizeLogs)($record);

    expect($sanitized->context['email'])->toBe('safe@example.test')
        ->and($sanitized->context['password'])->toBe('[REDACTED]')
        ->and($sanitized->context['nested']['authorization'])->toBe('[REDACTED]')
        ->and($sanitized->extra['api_key'])->toBe('[REDACTED]');
});
