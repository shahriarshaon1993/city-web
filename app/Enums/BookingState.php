<?php

namespace App\Enums;

/** FR-PROJ booking badge (`projects.booking_state`); `None` shows no badge. */
enum BookingState: string
{
    case BookingOpen = 'booking_open';
    case LimitedUnits = 'limited_units';
    case SoldOut = 'sold_out';
    case None = 'none';

    public function label(): ?string
    {
        return match ($this) {
            self::BookingOpen => 'Booking open',
            self::LimitedUnits => 'Limited units',
            self::SoldOut => 'Sold out',
            self::None => null,
        };
    }
}
