<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('type');
            $table->string('source')->index();
            $table->unsignedInteger('points');
            $table->unsignedInteger('points_balance_after');
            $table->foreignId('related_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_transactions');
    }
};
