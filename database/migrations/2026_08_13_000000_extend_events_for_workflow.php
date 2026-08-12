<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('organizer_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('event_type', 100)->default('general')->after('title');
            $table->string('committee')->nullable()->after('event_type');
            $table->string('status', 30)->default('draft')->after('duration_minutes')->index();
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('rejection_reason');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description')->index();
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['organizer_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'organizer_id',
                'event_type',
                'committee',
                'status',
                'rejection_reason',
                'submitted_at',
                'reviewed_at',
                'reviewed_by',
            ]);
        });
    }
};
