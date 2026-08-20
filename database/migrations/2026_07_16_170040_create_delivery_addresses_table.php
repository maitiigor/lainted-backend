<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lga_id')->constrained('lgas')->restrictOnDelete();
            $table->string('landmark');
            $table->text('full_address_encrypted');
            $table->text('recipient_phone_encrypted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_addresses');
    }
};
