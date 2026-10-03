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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('tourist')->after('email')->index();
            $table->string('phone', 20)->nullable()->after('role');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('google_id')->nullable()->unique()->after('phone_verified_at');
            $table->string('locale', 5)->default('en')->after('google_id');
            $table->timestamp('deactivated_at')->nullable()->after('locale');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone', 'phone_verified_at', 'google_id', 'locale', 'deactivated_at']);
        });
    }
};
