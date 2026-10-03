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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('pax')->default(1);
            $table->decimal('budget', 12, 2)->nullable();
            $table->time('day_starts_at')->default('08:00');
            $table->string('travel_mode')->default('car');
            $table->text('notes')->nullable();
            $table->string('share_token', 40)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
