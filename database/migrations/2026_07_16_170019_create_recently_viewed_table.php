<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recently_viewed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->timestamp('viewed_at');

            $table->unique(['user_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recently_viewed');
    }
};
