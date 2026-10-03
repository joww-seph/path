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
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedSmallInteger('default_daily_slots')->nullable()->after('is_bookable');
        });

        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('listing_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_id');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('default_daily_slots');
        });
    }
};
