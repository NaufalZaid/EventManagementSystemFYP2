<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('societies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('society_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('society_id')->nullable()->after('organizer_id')->constrained()->nullOnDelete();
        });

        $organizers = DB::table('users')->where('role', 'organizer')->get(['id']);

        if ($organizers->isNotEmpty()) {
            $legacySocietyId = DB::table('societies')->insertGetId([
                'name' => 'Legacy Organizers',
                'description' => 'Temporary society for organizer accounts that existed before society management was introduced.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->whereIn('id', $organizers->pluck('id'))->update(['society_id' => $legacySocietyId]);

            foreach ($organizers as $organizer) {
                DB::table('events')
                    ->where('organizer_id', $organizer->id)
                    ->whereNull('society_id')
                    ->update(['society_id' => $legacySocietyId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('society_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('society_id');
        });

        Schema::dropIfExists('societies');
    }
};
