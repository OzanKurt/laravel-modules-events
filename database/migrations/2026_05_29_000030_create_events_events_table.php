<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->json('title');
            $table->json('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('events_categories')->nullOnDelete();
            $table->string('status');
            $table->string('visibility');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone')->default('UTC');
            $table->string('location_name')->nullable();
            $table->text('location_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('cover_path')->nullable();
            $table->json('reminder_intervals')->nullable();
            $table->string('attendee_list_visibility')->default('organizer_only');

            // Recurrence
            $table->foreignId('parent_event_id')->nullable()->constrained('events_events')->cascadeOnDelete();
            $table->json('recurrence_rule')->nullable();

            // Capacity
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->text('cancellation_reason')->nullable();

            // Counters
            $table->unsignedBigInteger('tickets_sold_count')->default(0);
            $table->unsignedBigInteger('attendees_count')->default(0);
            $table->unsignedInteger('applications_pending_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
            $table->index('parent_event_id', 'events_events_parent_event_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_events');
    }
};
