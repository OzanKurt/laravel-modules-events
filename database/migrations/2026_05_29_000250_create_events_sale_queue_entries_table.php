<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_sale_queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->unsignedBigInteger('position');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_heartbeat_at');
            $table->string('status');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_sale_queue_entries');
    }
};
