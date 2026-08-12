<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('preferred_venue_id')->nullable()->after('duration_minutes')->constrained('venues')->nullOnDelete();
            $table->date('preferred_date')->nullable()->after('preferred_venue_id');
            $table->time('preferred_start_time')->nullable()->after('preferred_date');
        });

        Schema::create('optimization_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('completed')->index();
            $table->unsignedInteger('population_size');
            $table->unsignedInteger('generations');
            $table->decimal('mutation_rate', 5, 4);
            $table->unsignedInteger('events_count');
            $table->decimal('best_fitness', 12, 2);
            $table->unsignedInteger('hard_conflicts');
            $table->decimal('utilization_percent', 6, 2)->default(0);
            $table->unsignedInteger('execution_ms');
            $table->json('metrics')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('optimization_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('timeslot_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('soft_penalty', 10, 2)->default(0);
            $table->json('details')->nullable();
            $table->timestamps();
            $table->unique(['optimization_run_id', 'event_id'], 'optimization_run_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('optimization_assignments');
        Schema::dropIfExists('optimization_runs');
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['preferred_venue_id']);
            $table->dropColumn(['preferred_venue_id', 'preferred_date', 'preferred_start_time']);
        });
    }
};
