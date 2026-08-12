<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptimizationExperiment extends Model
{
    protected $fillable = [
        'created_by', 'name', 'repetitions', 'population_size', 'generations',
        'mutation_rate', 'base_seed', 'events_count', 'success_rate', 'average_fitness',
        'best_fitness', 'average_utilization', 'average_execution_ms', 'dataset', 'results',
    ];

    protected function casts(): array
    {
        return [
            'repetitions' => 'integer', 'population_size' => 'integer', 'generations' => 'integer',
            'base_seed' => 'integer', 'events_count' => 'integer', 'mutation_rate' => 'float',
            'success_rate' => 'float', 'average_fitness' => 'float',
            'best_fitness' => 'float', 'average_utilization' => 'float',
            'average_execution_ms' => 'float', 'dataset' => 'array', 'results' => 'array',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
