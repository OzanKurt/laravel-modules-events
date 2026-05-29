<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_ticket_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->string('slug');
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedBigInteger('price_minor');
            $table->string('currency', 3);
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedBigInteger('sold_count')->default(0);
            $table->boolean('scannable')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_ticket_add_ons');
    }
};
