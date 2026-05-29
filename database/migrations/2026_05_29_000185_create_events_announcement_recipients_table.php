<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('events_announcements')->cascadeOnDelete();
            $table->foreignId('attendee_id')->constrained('events_attendees')->cascadeOnDelete();
            $table->string('status');
            $table->string('notification_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'attendee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_announcement_recipients');
    }
};
