<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained('events_ticket_types')->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('price_minor');
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedBigInteger('sold_count')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['ticket_type_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_price_tiers');
    }
};
