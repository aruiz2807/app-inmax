<?php

namespace App\Enums;

enum MedicationStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
        };
    }
}
