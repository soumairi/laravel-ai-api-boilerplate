<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->ulid('family_id')->index();
            $table->char('token_hash', 64)->unique();
            $table->char('previous_token_hash', 64)->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('revoke_reason', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_sessions');
    }
};
