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
        Schema::create('itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('custom_title')->nullable();
            $table->decimal('custom_latitude', 10, 7)->nullable();
            $table->decimal('custom_longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('day_number');
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->time('fixed_start_time')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('travel_minutes_from_previous')->nullable();
            $table->decimal('distance_km_from_previous', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_done')->default(false);
            $table->uuid('client_uuid')->nullable()->unique();
            $table->timestamps();

            $table->index(['trip_id', 'day_number', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_items');
    }
};
