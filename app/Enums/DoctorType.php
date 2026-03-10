<?php

namespace App\Enums;

enum DoctorType: string
{
    case Doctor = 'Doctor';
    case Lab = 'Lab';
    case Hospital = 'Hospital';

    public function label(): string
    {
        return __('enums.doctor_type.' . $this->value);
    }
}
