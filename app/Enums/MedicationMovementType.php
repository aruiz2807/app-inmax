<?php

namespace App\Enums;

enum MedicationMovementType: string
{
    case IN = 'IN';
    case OUT = 'OUT';

    public function label(): string
    {
        return __('enums.medication_movement_type.' . $this->value);
    }
}
