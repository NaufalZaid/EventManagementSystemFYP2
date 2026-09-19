<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['society_id', 'name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $currentRole = $this->role instanceof UserRole ? $this->role->value : $this->role;

        return collect($roles)->contains(
            fn (UserRole|string $role) => $currentRole === ($role instanceof UserRole ? $role->value : $role)
        );
    }

    public function society()
    {
        return $this->belongsTo(Society::class);
    }

    public function organizedEvents()
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function canManageEvent(Event $event): bool
    {
        if ($this->hasRole(UserRole::Administrator)) {
            return true;
        }

        return $this->hasRole(UserRole::Organizer)
            && $this->society_id !== null
            && $this->society?->is_active
            && $this->society_id === $event->society_id;
    }

    public function eventRegistrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function personalCommitments()
    {
        return $this->hasMany(PersonalCommitment::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function evaluation()
    {
        return $this->hasOne(UserEvaluation::class);
    }
}
