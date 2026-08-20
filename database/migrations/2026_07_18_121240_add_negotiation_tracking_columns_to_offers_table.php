<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Rounds strictly alternate buyer/seller, but nothing records
            // which party proposed the CURRENT row - needed to know who's
            // allowed to respond (counter/accept/decline) to it.
            $table->string('initiated_by')->default('buyer')->after('parent_offer_id');
            $table->timestamp('reminder_sent_6h_at')->nullable()->after('checkout_expires_at');
            $table->timestamp('reminder_sent_2h_at')->nullable()->after('reminder_sent_6h_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['initiated_by', 'reminder_sent_6h_at', 'reminder_sent_2h_at']);
        });
    }
};
