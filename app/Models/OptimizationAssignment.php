<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptimizationAssignment extends Model
{
    protected $fillable = [
        'optimization_run_id', 'event_id', 'venue_id', 'timeslot_id', 'soft_penalty', 'details',
    ];

    protected function casts(): array
    {
        return ['soft_penalty' => 'float', 'details' => 'array'];
    }

    public function run()
    {
        return $this->belongsTo(OptimizationRun::class, 'optimization_run_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function timeslot()
    {
        return $this->belongsTo(Timeslot::class);
    }
}
