<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_leak_strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('detected_pattern');
            $table->string('matched_text')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_leak_strikes');
    }
};
