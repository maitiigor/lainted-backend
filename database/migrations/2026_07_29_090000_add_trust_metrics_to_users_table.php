<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->nullable()->after('seller_rank');
            $table->unsignedInteger('total_sales')->default(0)->after('rating');
            $table->decimal('trust_score', 5, 2)->nullable()->after('total_sales');
            $table->decimal('response_rate', 5, 2)->nullable()->after('trust_score');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rating', 'total_sales', 'trust_score', 'response_rate']);
        });
    }
};
