<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('events_orders')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained('events_ticket_types')->restrictOnDelete();
            $table->foreignId('price_tier_id')->nullable()->constrained('events_price_tiers')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('line_total_minor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_order_items');
    }
};
