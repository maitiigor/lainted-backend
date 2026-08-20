<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('type');
            $table->string('source')->index();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->decimal('balance_after', 12, 2)->unsigned();
            $table->string('reference')->nullable()->unique();
            $table->foreignId('related_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
