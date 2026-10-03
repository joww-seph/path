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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('summary', 300)->nullable();
            $table->text('description')->nullable();
            $table->string('barangay')->nullable()->index();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('opening_hours')->nullable();
            $table->decimal('entrance_fee', 10, 2)->nullable();
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();
            $table->unsignedSmallInteger('visit_minutes')->default(60);
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('website')->nullable();
            $table->string('facebook_url')->nullable();
            $table->boolean('is_bookable')->default(false);
            $table->boolean('is_accessible')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('draft')->index();
            $table->string('review_note')->nullable();
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
