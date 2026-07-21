<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\LogRecord;

final class SanitizeLogs
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->sanitize($record->context),
            extra: [
                ...$this->sanitize($record->extra),
                'request_id' => app()->bound('request') ? request()->attributes->get('request_id') : null,
            ],
        );
    }

    /** @param array<string|int, mixed> $values
     * @return array<string|int, mixed>
     */
    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match('/password|token|secret|authorization|cookie|key/i', $key)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }
}
