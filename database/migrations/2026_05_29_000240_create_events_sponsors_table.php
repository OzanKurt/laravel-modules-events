<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events_events')->cascadeOnDelete();
            $table->foreignId('sponsor_tier_id')->constrained('events_sponsor_tiers')->restrictOnDelete();
            $table->string('name');
            $table->foreignId('contact_user_id')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('website_url')->nullable();
            $table->text('blurb')->nullable();
            $table->string('status');
            $table->foreignId('order_id')->nullable()->constrained('events_orders')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_sponsors');
    }
};
