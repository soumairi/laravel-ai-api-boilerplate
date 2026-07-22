<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refresh_sessions', function (Blueprint $table): void {
            $table->index(['user_id', 'revoked_at'], 'refresh_sessions_user_revoked_index');
        });
    }

    public function down(): void
    {
        Schema::table('refresh_sessions', function (Blueprint $table): void {
            $table->dropIndex('refresh_sessions_user_revoked_index');
        });
    }
};
