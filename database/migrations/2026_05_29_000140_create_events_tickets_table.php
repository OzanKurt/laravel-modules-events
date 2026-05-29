<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('events_order_items')->cascadeOnDelete();
            $table->foreignId('order_item_assignment_id')->nullable()->constrained('events_order_item_assignments')->nullOnDelete();
            $table->foreignId('ticket_type_id')->constrained('events_ticket_types')->restrictOnDelete();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('holder_id')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->string('holder_name');
            $table->string('holder_email');
            $table->string('status');
            $table->string('qr_token')->unique();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->foreignId('transferred_from')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->foreignId('transfer_fee_order_id')->nullable()->constrained('events_orders')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_tickets');
    }
};
