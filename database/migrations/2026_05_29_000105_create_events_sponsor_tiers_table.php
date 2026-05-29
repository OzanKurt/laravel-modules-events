<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_sponsor_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->unsignedBigInteger('price_minor');
            $table->string('currency', 3);
            $table->unsignedInteger('comp_ticket_quota')->default(0);
            $table->foreignId('comp_ticket_type_id')->nullable()->constrained('events_ticket_types')->nullOnDelete();
            $table->json('benefits')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_sponsor_tiers');
    }
};
