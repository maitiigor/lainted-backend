<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('personal_commission_rate', 5, 2)->unsigned()->default(6.00);
            $table->timestamps();
        });

        // Singleton row, seeded here so PlatformSetting::current() never
        // has to handle a missing row in any environment that's migrated.
        DB::table('platform_settings')->insert([
            'personal_commission_rate' => 6.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
