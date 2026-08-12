<?php

namespace App\Enums;

enum VenueRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
