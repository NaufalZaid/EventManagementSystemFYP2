<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    protected $fillable = [
        'event_id',
        'venue_id',
        'timeslot_id',
        'status',
    ];

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
    } //
}
