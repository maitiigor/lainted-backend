<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('code');
            $table->string('discount_type');
            $table->decimal('discount_value', 10, 2)->unsigned();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['seller_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
