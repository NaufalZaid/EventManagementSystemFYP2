<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'description',
        'is_active',
    ]; //

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function blackouts()
    {
        return $this->hasMany(VenueBlackout::class);
    }

    public function requests()
    {
        return $this->hasMany(VenueRequest::class);
    }
}
