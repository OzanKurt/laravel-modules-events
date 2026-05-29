<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_event_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('payload');
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('used_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['owner_id', 'slug']);
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_event_templates');
    }
};
