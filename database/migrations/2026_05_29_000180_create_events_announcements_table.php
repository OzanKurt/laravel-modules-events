<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained(config('auth.providers.users.table', 'users'))->restrictOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('audience');
            $table->json('audience_filter')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_announcements');
    }
};
