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
        Schema::table('trips', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('share_token');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });
    }
};
