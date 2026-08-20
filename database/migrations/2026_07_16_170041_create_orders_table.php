<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->restrictOnDelete();
            $table->foreignId('bundle_id')->nullable()->constrained('bundles')->restrictOnDelete();
            $table->foreignId('offer_id')->nullable()->unique()->constrained('offers')->restrictOnDelete();
            $table->foreignId('delivery_address_id')->constrained('delivery_addresses')->restrictOnDelete();
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->unsigned()->default(0);
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('delivery_tier');
            $table->string('payment_method')->default('online');
            $table->string('logistics_provider')->nullable();
            $table->string('tracking_reference')->nullable();
            $table->decimal('item_price', 12, 2)->unsigned();
            $table->decimal('commission_rate', 5, 2)->unsigned()->nullable();
            $table->decimal('commission_amount', 10, 2)->unsigned()->default(0);
            $table->decimal('protection_fee', 10, 2)->unsigned()->default(0);
            $table->decimal('delivery_fee', 10, 2)->unsigned()->default(0);
            $table->decimal('total_amount', 12, 2)->unsigned();
            $table->string('status')->default('pending')->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('dispute_deadline_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->char('pickup_code', 4)->nullable();
            $table->timestamp('pickup_code_entered_at')->nullable();
            $table->string('package_photo_url')->nullable();
            $table->timestamp('buyer_acknowledged_at')->nullable();
            $table->string('delivery_photo_url')->nullable();
            $table->boolean('delivery_confirmed_code')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
