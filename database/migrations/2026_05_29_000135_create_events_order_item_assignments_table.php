<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_order_item_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('events_order_items')->cascadeOnDelete();
            $table->unsignedInteger('seat_index');
            $table->foreignId('holder_user_id')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->string('holder_name');
            $table->string('holder_email');
            $table->json('holder_metadata')->nullable();
            $table->timestamps();

            $table->unique(['order_item_id', 'seat_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_order_item_assignments');
    }
};
