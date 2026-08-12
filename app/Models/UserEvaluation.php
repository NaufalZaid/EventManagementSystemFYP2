<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEvaluation extends Model
{
    protected $fillable = [
        'user_id', 'role', 'ease_of_use', 'usefulness', 'scheduling_confidence',
        'satisfaction', 'comments', 'consent', 'submitted_at',
    ];

    protected function casts(): array
    {
        return ['consent' => 'boolean', 'submitted_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
