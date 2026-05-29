<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_session_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('events_sessions')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('events_tickets')->cascadeOnDelete();
            $table->timestamp('checked_in_at');
            $table->foreignId('checked_in_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_session_check_ins');
    }
};
