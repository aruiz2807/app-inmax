<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rfc',
        'address',
        'phone',
        'email',
    ];

    public function purchases()
    {
        return $this->hasMany(MedicationPurchase::class);
    }
}
