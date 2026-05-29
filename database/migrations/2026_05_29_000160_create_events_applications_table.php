<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained('events_ticket_types')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('submitted_at');
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->text('decision_note')->nullable();

            $table->foreignId('reservation_order_id')->nullable()->constrained('events_orders')->nullOnDelete();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['applicant_id', 'ticket_type_id']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_applications');
    }
};
