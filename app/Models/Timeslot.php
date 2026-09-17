<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function setStartTimeAttribute(?string $value): void
    {
        $this->attributes['start_time'] = $value === null
            ? null
            : Carbon::parse($value)->startOfHour()->format('H:i:s');
    }

    public function setEndTimeAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['end_time'] = null;

            return;
        }

        $time = Carbon::parse($value);
        if ($time->minute !== 0 || $time->second !== 0) {
            $time->addHour()->startOfHour();
        }

        $this->attributes['end_time'] = $time->format('H:i:s');
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
