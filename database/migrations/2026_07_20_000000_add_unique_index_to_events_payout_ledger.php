<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events_payout_ledger', function (Blueprint $table) {
            // Guarantees at most one payout ledger entry per (order, organizer) so
            // that re-accruing a paid order cannot create duplicate entries.
            $table->unique(['order_id', 'organizer_user_id'], 'events_payout_ledger_order_organizer_unique');
        });
    }

    public function down(): void
    {
        Schema::table('events_payout_ledger', function (Blueprint $table) {
            $table->dropUnique('events_payout_ledger_order_organizer_unique');
        });
    }
};
