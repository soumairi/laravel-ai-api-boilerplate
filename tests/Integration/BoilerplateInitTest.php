<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('initializes dependencies keys and OpenAPI without destructive database work', function (): void {
    $environment = base_path('.env');
    $private = storage_path('keys/jwt-private.pem');
    $public = storage_path('keys/jwt-public.pem');
    File::delete([$environment, $private, $public]);

    try {
        expect(Artisan::call('boilerplate:init', [
            '--force' => true,
            '--skip-migrate' => true,
            '--skip-seed' => true,
        ]))->toBe(0)
            ->and(File::exists($environment))->toBeTrue()
            ->and(File::exists($private))->toBeTrue()
            ->and(File::exists($public))->toBeTrue()
            ->and(decoct(fileperms($private) & 0777))->toBe('600');
    } finally {
        File::put($environment, '');
        File::delete([$private, $public]);
    }
});

it('refuses production initialization without force', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');

    try {
        expect(Artisan::call('boilerplate:init', [
            '--skip-migrate' => true,
            '--skip-seed' => true,
        ]))->toBe(1);
    } finally {
        app()->detectEnvironment(static fn (): string => 'testing');
    }
});
