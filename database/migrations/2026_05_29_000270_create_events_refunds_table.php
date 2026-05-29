<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('events_orders')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('events_tickets')->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('reason');
            $table->text('reason_note')->nullable();
            $table->string('status');
            $table->string('processor_reference')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_refunds');
    }
};
