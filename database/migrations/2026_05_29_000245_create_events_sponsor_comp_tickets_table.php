<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_sponsor_comp_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('events_sponsors')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('events_tickets')->cascadeOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['sponsor_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_sponsor_comp_tickets');
    }
};
