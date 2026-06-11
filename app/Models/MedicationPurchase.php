<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicationPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'invoice',
        'subtotal',
        'total',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details()
    {
        return $this->hasMany(MedicationPurchaseDetail::class);
    }

    public function movements()
    {
        return $this->hasMany(MedicationMovement::class, 'purchase_id');
    }
}
