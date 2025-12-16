<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_gateway', 32)->nullable()->after('payment_method');
            $table->string('gateway_transaction_reference', 100)->nullable()->after('payment_gateway')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['gateway_transaction_reference']);
            $table->dropColumn(['payment_gateway', 'gateway_transaction_reference']);
        });
    }
};


