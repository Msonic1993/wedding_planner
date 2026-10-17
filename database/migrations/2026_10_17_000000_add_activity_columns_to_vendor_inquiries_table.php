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
            $table->unsignedInteger('messages_count')->default(0)->after('requested_date');
            $table->unsignedBigInteger('last_message_id')->nullable()->after('messages_count');
            $table->timestamp('last_message_at')->nullable()->after('last_message_id');

            $table->index(['couple_user_id', 'last_message_at'], 'vendor_inquiries_couple_activity_index');
            $table->index(['vendor_profile_id', 'last_message_at'], 'vendor_inquiries_vendor_activity_index');
        });

        DB::table('vendor_inquiries')->update([
            'messages_count' => DB::raw('(SELECT COUNT(*) FROM chat_messages WHERE chat_messages.vendor_inquiry_id = vendor_inquiries.id)'),
            'last_message_id' => DB::raw('(SELECT MAX(id) FROM chat_messages WHERE chat_messages.vendor_inquiry_id = vendor_inquiries.id)'),
            'last_message_at' => DB::raw('(SELECT MAX(created_at) FROM chat_messages WHERE chat_messages.vendor_inquiry_id = vendor_inquiries.id)'),
        ]);
    }

    public function down(): void
    {
        $hasCoupleIndex = Schema::hasIndex('vendor_inquiries', ['couple_user_id']);

        Schema::table('vendor_inquiries', function (Blueprint $table) use ($hasCoupleIndex): void {
            // MySQL reuses the composite index for the couple_user_id foreign key, so restore a plain one first.
            if (! $hasCoupleIndex) {
                $table->index('couple_user_id', 'vendor_inquiries_couple_user_id_index');
            }

            $table->dropIndex('vendor_inquiries_couple_activity_index');
            $table->dropIndex('vendor_inquiries_vendor_activity_index');
            $table->dropColumn(['messages_count', 'last_message_id', 'last_message_at']);
        });
    }
};
