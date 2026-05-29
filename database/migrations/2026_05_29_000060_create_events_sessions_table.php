<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->string('slug');
            $table->json('title');
            $table->json('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('location_name')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedBigInteger('attendees_count')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'slug']);
            $table->index(['event_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_sessions');
    }
};
