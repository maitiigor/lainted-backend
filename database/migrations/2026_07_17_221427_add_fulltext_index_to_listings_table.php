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
        // The test suite runs against an in-memory SQLite DB (see
        // phpunit.xml), which has no FULLTEXT support at all - keyword
        // search itself is skipped there, not worth a SQLite fallback.
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->supportsFullText()) {
            return;
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->dropFullText(['title', 'description']);
        });
    }

    private function supportsFullText(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true);
    }
};
