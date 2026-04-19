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
    ]; //
    
    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }
}
