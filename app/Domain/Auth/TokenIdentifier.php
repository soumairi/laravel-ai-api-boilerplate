<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use InvalidArgumentException;

final readonly class TokenIdentifier
{
    private function __construct(public string $value)
    {
        if (strlen($value) < 64) {
            throw new InvalidArgumentException('Refresh token identifiers must contain at least 64 characters.');
        }
    }

    public static function generate(): self
    {
        return new self(rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '='));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function hash(string $key): string
    {
        return hash_hmac('sha256', $this->value, $key);
    }
}
