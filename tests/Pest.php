<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\ApiAssertions;
use Tests\TestCase;

$environmentPath = dirname(__DIR__).'/.env';
if (! is_file($environmentPath)) {
    touch($environmentPath);
}

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
})->in('Feature', 'Integration', 'Security');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class, ApiAssertions::class)
    ->in('Feature', 'Integration', 'Security');

pest()
    ->extend(TestCase::class)
    ->in('Unit');
