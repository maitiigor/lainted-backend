<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->cascadeOnDelete();
            $table->unsignedTinyInteger('discount_percent');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
