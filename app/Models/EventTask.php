<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTask extends Model
{
    protected $fillable = ['event_id', 'title', 'description', 'priority', 'due_date', 'completed_at'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
