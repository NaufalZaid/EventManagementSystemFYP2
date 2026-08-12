<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Organizer = 'organizer';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Organizer => 'Event Organizer',
            self::Administrator => 'Administrator',
        };
    }
}
