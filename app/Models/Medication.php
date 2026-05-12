<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $trade_name
 * @property string $active_substance
 * @property string $lab
 * @property string $packaging
 * @property numeric $price_public
 * @property numeric $price_members
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereActiveSubstance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereLab($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication wherePackaging($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication wherePriceMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication wherePricePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereTradeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medication whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Medication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'trade_name',
        'active_substance',
        'lab',
        'packaging',
        'price_public',
        'price_members',
        'status',
    ];
}
