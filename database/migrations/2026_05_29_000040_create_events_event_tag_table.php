<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_event_tag', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('events_tags')->cascadeOnDelete();
            $table->primary(['event_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_event_tag');
    }
};
