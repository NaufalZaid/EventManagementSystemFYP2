<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalCommitment extends Model
{
    protected $fillable = [
        'user_id', 'title', 'commitment_type', 'location', 'description', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
