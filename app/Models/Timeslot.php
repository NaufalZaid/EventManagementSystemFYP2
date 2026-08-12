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

    protected function casts(): array
    {
        return ['slot_date' => 'date'];
    }

    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function venueRequests()
    {
        return $this->hasMany(VenueRequest::class);
    }
}
