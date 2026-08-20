<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('item_type');
            $table->string('condition_grade')->index();
            $table->text('fault_description')->nullable();
            $table->string('size')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('serial_number_encrypted')->nullable();
            $table->string('weight_tier');
            $table->decimal('price', 12, 2)->unsigned();
            $table->boolean('is_negotiable')->default(true);
            $table->decimal('minimum_price', 12, 2)->unsigned()->nullable();
            $table->boolean('is_functional')->nullable();
            $table->boolean('requires_installation')->default(false);
            $table->foreignId('lga_id')->constrained('lgas')->restrictOnDelete();
            $table->string('landmark')->nullable();
            $table->string('listing_source');
            $table->boolean('is_bale')->default(false);
            $table->string('bale_type')->nullable();
            $table->unsignedInteger('bale_item_count')->nullable();
            $table->string('bale_origin_country')->nullable();
            $table->boolean('is_wardrobe_cleanout')->default(false);
            $table->string('verification_status')->default('not_submitted');
            $table->string('verification_video_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_platform_verified')->default(false);
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
