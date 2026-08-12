<?php

namespace App\Models;

use App\Enums\VenueRequestStatus;
use Illuminate\Database\Eloquent\Model;

class VenueRequest extends Model
{
    protected $fillable = [
        'event_id', 'venue_id', 'timeslot_id', 'requested_by', 'status',
        'organizer_notes', 'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['status' => VenueRequestStatus::class, 'reviewed_at' => 'datetime'];
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

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
