<?php

namespace App\Enums;

enum DoctorType: string
{
    case Doctor = 'Doctor';
    case Provider = 'Provider';

    public function label(): string
    {
        return __('enums.doctor_type.' . $this->value);
    }
}
