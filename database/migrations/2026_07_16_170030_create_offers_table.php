<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->unsignedTinyInteger('round_number')->default(1);
            $table->string('status')->default('pending')->index();
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('agreed_at')->nullable();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
