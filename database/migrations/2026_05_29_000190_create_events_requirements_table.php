<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->nullable()->constrained('events_ticket_types')->cascadeOnDelete();
            $table->string('type');
            $table->json('payload');
            $table->boolean('strict')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_requirements');
    }
};
