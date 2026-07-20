<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events_orders', function (Blueprint $table) {
            // Supports the ExpirePendingOrders sweep, which scans for pending orders
            // older than the cart timeout (status + created_at).
            $table->index(['status', 'created_at'], 'events_orders_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('events_orders', function (Blueprint $table) {
            $table->dropIndex('events_orders_status_created_at_index');
        });
    }
};
