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
        Schema::create('template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('custom_title')->nullable();
            $table->unsignedSmallInteger('day_number')->default(1);
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_items');
    }
};
