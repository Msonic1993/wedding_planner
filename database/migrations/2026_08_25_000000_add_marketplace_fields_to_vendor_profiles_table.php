<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table): void {
            $table->decimal('price_from', 12, 2)->nullable()->after('categories');
            $table->decimal('price_to', 12, 2)->nullable()->after('price_from');
            $table->date('available_from')->nullable()->after('price_to');
            $table->date('available_to')->nullable()->after('available_from');
            $table->index(['voivodeship', 'price_from']);
        });
    }

    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table): void {
            $table->dropIndex(['voivodeship', 'price_from']);
            $table->dropColumn(['price_from', 'price_to', 'available_from', 'available_to']);
        });
    }
};
