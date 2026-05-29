<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->string('status');
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('discount_minor');
            $table->unsignedBigInteger('tax_minor');
            $table->unsignedBigInteger('total_minor');
            $table->unsignedInteger('tax_rate_basis_points')->nullable();
            $table->string('currency', 3);
            $table->foreignId('discount_code_id')->nullable()->constrained('events_discount_codes')->nullOnDelete();
            $table->foreignId('referral_link_id')->nullable()->constrained('events_referral_links')->nullOnDelete();
            $table->string('processor')->nullable();
            $table->string('processor_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Group ticket assignment
            $table->timestamp('assignment_completed_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_orders');
    }
};
