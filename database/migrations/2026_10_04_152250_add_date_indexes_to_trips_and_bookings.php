<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Date-range lookups used by analytics, advisory notices and the reminder jobs.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->index(['start_date', 'end_date']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['start_date', 'end_date']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['date', 'status']);
        });
    }
};
