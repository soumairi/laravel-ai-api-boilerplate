<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Testing\TestResponse;

trait ApiAssertions
{
    protected function assertApiSuccess(TestResponse $response, int $status = 200): TestResponse
    {
        $response
            ->assertStatus($status)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertJsonStructure(['data', 'request_id']);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        return $response;
    }

    protected function assertProblem(TestResponse $response, int $status): TestResponse
    {
        return $response
            ->assertStatus($status)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', $status)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance', 'request_id']);
    }

    protected function bearer(string $accessToken): array
    {
        return [
            'Authorization' => 'Bearer '.$accessToken,
            'Accept' => 'application/json',
        ];
    }
}
