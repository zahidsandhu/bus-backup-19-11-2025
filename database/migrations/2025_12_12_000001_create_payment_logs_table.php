<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('gateway')->default('jazzcash');
            $table->string('txn_reference', 100)->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status', 32);
            $table->string('response_code', 32)->nullable();
            $table->string('message')->nullable();
            $table->json('request_payload');
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};


