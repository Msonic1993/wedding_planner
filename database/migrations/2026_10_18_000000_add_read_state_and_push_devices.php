<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_inquiries', function (Blueprint $table): void {
            $table->unsignedBigInteger('couple_last_read_message_id')->nullable()->after('last_message_at');
            $table->unsignedBigInteger('vendor_last_read_message_id')->nullable()->after('couple_last_read_message_id');
            $table->unsignedInteger('couple_unread_count')->default(0)->after('vendor_last_read_message_id');
            $table->unsignedInteger('vendor_unread_count')->default(0)->after('couple_unread_count');
        });

        // Existing threads start as read, so the first release does not flood users with badges.
        DB::table('vendor_inquiries')->update([
            'couple_last_read_message_id' => DB::raw('last_message_id'),
            'vendor_last_read_message_id' => DB::raw('last_message_id'),
        ]);

        Schema::create('push_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform', 16);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_devices');

        Schema::table('vendor_inquiries', function (Blueprint $table): void {
            $table->dropColumn([
                'couple_last_read_message_id',
                'vendor_last_read_message_id',
                'couple_unread_count',
                'vendor_unread_count',
            ]);
        });
    }
};
