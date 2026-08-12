<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderDelivery extends Model
{
    protected $fillable = ['event_registration_id', 'lead_minutes', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function registration()
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}
