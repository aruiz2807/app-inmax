<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicationPurchaseDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'medication_purchase_id',
        'medication_id',
        'requested_quantity',
        'received_quantity',
        'price',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'received_quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(
            MedicationPurchase::class,
            'medication_purchase_id'
        );
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}
