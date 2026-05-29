<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_payout_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('events_orders')->cascadeOnDelete();
            $table->foreignId('organizer_user_id')->constrained(config('auth.providers.users.table', 'users'))->restrictOnDelete();
            $table->unsignedInteger('share_basis_points');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('status');
            $table->timestamp('paid_out_at')->nullable();
            $table->string('payout_reference')->nullable();
            $table->timestamps();

            $table->index(['organizer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events_payout_ledger');
    }
};
