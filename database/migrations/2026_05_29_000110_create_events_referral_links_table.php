<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_referral_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events_events')->nullOnDelete();
            $table->foreignId('organizer_id')->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('landing_path')->nullable();
            $table->unsignedInteger('commission_basis_points')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedBigInteger('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organizer_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_referral_links');
    }
};
