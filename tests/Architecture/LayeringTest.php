<?php

declare(strict_types=1);

arch('domain remains framework independent')
    ->expect('App\Domain')
    ->not->toUse([
        'Illuminate',
        'Dedoc',
        'Spatie',
        'PHPOpenSourceSaver',
    ]);

arch('application services do not depend on HTTP controllers')
    ->expect('App\Application')
    ->not->toUse('App\Http\Controllers');

arch('authentication actions are final and token data is immutable')
    ->expect('App\Application\Auth\Actions')
    ->toBeFinal()
    ->and([
        'App\Application\Auth\ClientContext',
        'App\Application\Auth\RefreshToken',
        'App\Application\Auth\RotatedRefreshToken',
        'App\Application\Auth\RotatedTokenPair',
        'App\Application\Auth\TokenClaims',
        'App\Application\Auth\TokenPair',
        'App\Domain\Auth\TokenIdentifier',
    ])
    ->toBeFinal()
    ->toBeReadonly();

arch('controllers are final and versioned')
    ->expect('App\Http\Controllers\Api\V1')
    ->toBeFinal()
    ->toHaveSuffix('Controller');

arch('test code contains no focused or skipped markers')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();
