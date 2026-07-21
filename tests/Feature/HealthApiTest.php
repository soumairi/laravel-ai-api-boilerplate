<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('reports live and ready health without infrastructure details', function (): void {
    $this->assertApiSuccess($this->getJson('/health/live'))
        ->assertJsonPath('data.status', 'ok');
    $this->assertApiSuccess($this->getJson('/health/ready'))
        ->assertJsonPath('data.status', 'ready')
        ->assertJsonMissingPath('data.database')
        ->assertJsonMissingPath('data.redis');
});

it('returns safe readiness failure details', function (): void {
    DB::shouldReceive('select')->once()->andThrow(new RuntimeException('sensitive connection detail'));

    $this->getJson('/health/ready')
        ->assertStatus(503)
        ->assertJsonPath('detail', 'A required dependency is unavailable.')
        ->assertJsonMissing(['sensitive connection detail']);
});
