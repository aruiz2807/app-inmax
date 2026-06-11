<?php

namespace App\Enums;

enum MedicationPurchasesStatus: string
{
    case Requested = 'requested';
    case Received = 'received';
    case Partial = 'partial';
    case Closed = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Requested => 'Solicitado',
            self::Received => 'Recibido',
            self::Partial => 'Parcial',
            self::Closed => 'Cerrado',
        };
    }
}
