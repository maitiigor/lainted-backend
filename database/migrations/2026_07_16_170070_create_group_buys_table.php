<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_buys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('target_amount', 12, 2)->unsigned();
            $table->string('status')->default('open')->index();
            $table->timestamp('deadline_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_buys');
    }
};
