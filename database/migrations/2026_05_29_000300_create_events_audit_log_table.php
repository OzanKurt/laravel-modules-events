<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events_events')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained(config('auth.providers.users.table', 'users'))->nullOnDelete();
            $table->string('actor_type')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('changes')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['event_id', 'occurred_at']);
            $table->index('action');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_audit_log');
    }
};
