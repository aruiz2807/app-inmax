<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case REQUESTED = 'Requested';
    case REJECTED = 'Rejected';
    case BOOKED = 'Booked';
    case CANCELLED = 'Cancelled';
    case COMPLETED = 'Completed';
    case NO_SHOW = 'No-show';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED => 'Solicitada',
            self::REJECTED => 'Rechazada',
            self::BOOKED => 'Agendada',
            self::CANCELLED => 'Cancelada',
            self::COMPLETED => 'Atendida',
            self::NO_SHOW => 'No se presentó',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CANCELLED, self::NO_SHOW, self::REJECTED => 'red',
            self::COMPLETED, self::BOOKED => 'teal',
            self::REQUESTED => 'yellow',
            default => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CANCELLED, self::REJECTED => 'x-circle',
            self::NO_SHOW => 'eye-slash',
            self::COMPLETED => 'shield-check',
            self::BOOKED => 'calendar',
            self::REQUESTED => 'clock',
            default => 'information-circle',
        };
    }
}
