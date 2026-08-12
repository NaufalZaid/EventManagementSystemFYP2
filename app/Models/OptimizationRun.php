<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptimizationRun extends Model
{
    protected $fillable = [
        'created_by', 'status', 'population_size', 'generations', 'mutation_rate',
        'events_count', 'best_fitness', 'hard_conflicts', 'utilization_percent',
        'execution_ms', 'metrics', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'mutation_rate' => 'float', 'best_fitness' => 'float',
            'utilization_percent' => 'float', 'metrics' => 'array', 'applied_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(OptimizationAssignment::class);
    }
}
