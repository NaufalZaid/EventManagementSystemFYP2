<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timeslot extends Model
{
    protected $fillable = [
        'slot_date',
        'start_time',
        'end_time',
    ];
    
    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }
}
