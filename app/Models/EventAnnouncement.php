<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAnnouncement extends Model
{
    protected $fillable = ['event_id', 'created_by', 'title', 'message', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
