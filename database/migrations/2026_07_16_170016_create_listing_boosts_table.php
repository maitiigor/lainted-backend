<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_boosts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2)->unsigned();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_boosts');
    }
};
