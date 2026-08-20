<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // A seller's saved default pickup location for real courier
            // bookings (see SendboxService) - reuses the same
            // delivery_addresses table the buyer-side address already
            // lives in, not a parallel model. Set once, not per order.
            $table->foreignId('pickup_address_id')->nullable()->after('landmark')
                ->constrained('delivery_addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_address_id');
        });
    }
};
