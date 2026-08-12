<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueBlackout extends Model
{
    protected $fillable = ['venue_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
