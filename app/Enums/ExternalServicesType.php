<?php

namespace App\Enums;

enum ExternalServicesType: string
{
    case Prescription = 'Prescription';
    case Diagnosis = 'Diagnosis';
    case Analysis = 'Analysis';
    case Vaccine = 'Vaccine';

    public function label(): string
    {
        return __('enums.external_services_type.' . $this->value);
    }
}