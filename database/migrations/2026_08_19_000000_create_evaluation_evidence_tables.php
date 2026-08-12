<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optimization_experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('repetitions');
            $table->unsignedInteger('population_size');
            $table->unsignedInteger('generations');
            $table->decimal('mutation_rate', 5, 4);
            $table->unsignedBigInteger('base_seed');
            $table->unsignedInteger('events_count');
            $table->decimal('success_rate', 6, 2);
            $table->decimal('average_fitness', 12, 2);
            $table->decimal('best_fitness', 12, 2);
            $table->decimal('average_utilization', 6, 2);
            $table->decimal('average_execution_ms', 12, 2);
            $table->json('dataset');
            $table->json('results');
            $table->timestamps();
        });

        Schema::create('user_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('role', 30)->index();
            $table->unsignedTinyInteger('ease_of_use');
            $table->unsignedTinyInteger('usefulness');
            $table->unsignedTinyInteger('scheduling_confidence');
            $table->unsignedTinyInteger('satisfaction');
            $table->text('comments')->nullable();
            $table->boolean('consent')->default(false);
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_evaluations');
        Schema::dropIfExists('optimization_experiments');
    }
};
