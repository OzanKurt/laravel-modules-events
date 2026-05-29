<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_ticket_type_session', function (Blueprint $table) {
            $table->foreignId('ticket_type_id')->constrained('events_ticket_types')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('events_sessions')->cascadeOnDelete();
            $table->primary(['ticket_type_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_ticket_type_session');
    }
};
