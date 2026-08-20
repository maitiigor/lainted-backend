<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_buy_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_buy_id')->constrained('group_buys')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('share_amount', 10, 2)->unsigned();
            $table->string('payment_status')->default('pending');
            $table->timestamps();

            $table->unique(['group_buy_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_buy_participants');
    }
};
