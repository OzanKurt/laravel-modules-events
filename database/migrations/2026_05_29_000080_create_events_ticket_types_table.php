<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->string('slug');
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('mode');
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('refundable')->default(true);
            $table->unsignedInteger('self_cancel_deadline_hours_before_event')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedBigInteger('sold_count')->default(0);
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->unsignedInteger('max_per_order')->default(10);
            $table->unsignedBigInteger('minimum_price_minor')->nullable();
            $table->unsignedBigInteger('suggested_price_minor')->nullable();
            $table->foreignId('attendance_form_id')->nullable()->constrained('events_attendance_forms')->nullOnDelete();

            // Transfers
            $table->boolean('transferable')->default(true);
            $table->unsignedInteger('transfer_deadline_hours_before_event')->nullable();
            $table->unsignedBigInteger('transfer_fee_minor')->nullable();
            $table->string('transfer_fee_currency', 3)->nullable();

            // EU consumer-protection refund window
            $table->boolean('consumer_protection_exempt')->default(false);

            $table->json('metadata')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_ticket_types');
    }
};
