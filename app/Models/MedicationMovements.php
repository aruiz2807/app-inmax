<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicationMovements extends Model
{
    protected $fillable = [
        'medication_id',
        'type',
        'adjustment',
        'quantity',
        'reference',
        'prescription_id',
        'medication_purchase_id',
        'user_id',
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicationPurchase()
    {
        return $this->belongsTo(MedicationPurchase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
