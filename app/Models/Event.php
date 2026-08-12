<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'organizer_id',
        'title',
        'event_type',
        'committee',
        'description',
        'capacity',
        'duration_minutes',
        'preferred_venue_id',
        'preferred_date',
        'preferred_start_time',
        'status',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ]; //

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'preferred_date' => 'date',
        ];
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function preferredVenue()
    {
        return $this->belongsTo(Venue::class, 'preferred_venue_id');
    }

    public function optimizationAssignments()
    {
        return $this->hasMany(OptimizationAssignment::class);
    }

    public function schedules()
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function venueRequests()
    {
        return $this->hasMany(VenueRequest::class);
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function registeredParticipants()
    {
        return $this->registrations()->where('status', 'registered');
    }

    public function tasks()
    {
        return $this->hasMany(EventTask::class);
    }

    public function announcements()
    {
        return $this->hasMany(EventAnnouncement::class);
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
