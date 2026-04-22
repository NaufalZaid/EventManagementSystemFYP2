<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'capacity',
        'duration_minutes',
    ]; //
    
    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }
}

