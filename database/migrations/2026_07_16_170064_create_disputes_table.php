<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('raised_by')->constrained('users')->cascadeOnDelete();
            $table->string('dispute_type');
            $table->text('buyer_statement');
            $table->json('buyer_photos')->nullable();
            $table->text('seller_statement')->nullable();
            $table->json('seller_photos')->nullable();
            $table->decimal('self_resolution_offered', 10, 2)->unsigned()->nullable();
            $table->boolean('self_resolution_accepted')->nullable();
            $table->string('status')->default('open')->index();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('self_resolve_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
