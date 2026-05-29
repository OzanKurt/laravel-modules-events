<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained('events_ticket_types')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status');
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('claim_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_type_id', 'user_id']);
            $table->index(['ticket_type_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_waitlist_entries');
    }
};
