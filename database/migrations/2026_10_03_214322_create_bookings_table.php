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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rate_name');
            $table->decimal('unit_price', 10, 2);
            $table->string('unit');
            $table->date('date');
            $table->time('time')->nullable();
            $table->unsignedSmallInteger('nights')->default(1);
            $table->unsignedSmallInteger('pax');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->text('tourist_note')->nullable();
            $table->string('partner_note')->nullable();
            $table->string('qr_token', 64)->nullable()->unique();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['listing_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
