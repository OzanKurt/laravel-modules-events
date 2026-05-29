<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_ticket_add_on_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('events_tickets')->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained('events_ticket_add_ons')->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('events_order_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('line_total_minor');
            $table->string('status');
            $table->string('qr_token')->nullable()->unique();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_ticket_add_on_purchases');
    }
};
