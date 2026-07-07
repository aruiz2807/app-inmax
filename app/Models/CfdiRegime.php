<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PolicyLegalInformation> $policyLegalInformation
 * @property-read int|null $policy_legal_information_count
 * @mixin \Eloquent
 */
class CfdiRegime extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cfdi_regimes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Get the legal information records that use this CFDI regime.
     */
    public function policyLegalInformation(): HasMany
    {
        return $this->hasMany(PolicyLegalInformation::class, 'cfdi_regime_id');
    }
}
