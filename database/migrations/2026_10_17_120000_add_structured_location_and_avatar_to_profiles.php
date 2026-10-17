<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_profiles', function (Blueprint $table): void {
            $table->char('country_code', 2)->nullable()->after('location');
            $table->string('region_code', 20)->nullable()->after('country_code');
            $table->unsignedBigInteger('city_id')->nullable()->after('region_code');
            $table->string('avatar_key', 32)->nullable()->after('photo_path');
            $table->index(['country_code', 'region_code']);
        });

        Schema::table('vendor_profiles', function (Blueprint $table): void {
            $table->char('country_code', 2)->nullable()->after('social_media');
            $table->string('region_code', 20)->nullable()->after('country_code');
            $table->json('city_ids')->nullable()->after('cities');
            $table->index(['country_code', 'region_code']);
        });
    }

    public function down(): void
    {
        Schema::table('wedding_profiles', function (Blueprint $table): void {
            $table->dropIndex(['country_code', 'region_code']);
            $table->dropColumn(['country_code', 'region_code', 'city_id', 'avatar_key']);
        });

        Schema::table('vendor_profiles', function (Blueprint $table): void {
            $table->dropIndex(['country_code', 'region_code']);
            $table->dropColumn(['country_code', 'region_code', 'city_ids']);
        });
    }
};
