<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property string $legal_name
 * @property string $legal_address
 * @property int $legal_relationship_id
 * @property string $cfdi_rfc
 * @property string $cfdi_name
 * @property string $cfdi_postal_code
 * @property int $cfdi_regime_id
 * @property int $cfdi_use_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CfdiRegime $cfdiRegime
 * @property-read \App\Models\CfdiUse $cfdiUse
 * @property-read \App\Models\Policy $policy
 * @property-read \App\Models\Relationship $relationship
 * @mixin \Eloquent
 */
class PolicyLegalInformation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'policy_legal_information';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'policy_id',
        'legal_name',
        'legal_address',
        'legal_relationship_id',
        'cfdi_rfc',
        'cfdi_name',
        'cfdi_postal_code',
        'cfdi_regime_id',
        'cfdi_use_id',
    ];

    /**
     * Get the policy that owns this legal information.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * Get the relationship for the legal representation.
     */
    public function relationship(): BelongsTo
    {
        return $this->belongsTo(Relationship::class, 'legal_relationship_id');
    }

    /**
     * Get the CFDI regime for this legal information.
     */
    public function cfdiRegime(): BelongsTo
    {
        return $this->belongsTo(CfdiRegime::class, 'cfdi_regime_id');
    }

    /**
     * Get the CFDI use for this legal information.
     */
    public function cfdiUse(): BelongsTo
    {
        return $this->belongsTo(CfdiUse::class, 'cfdi_use_id');
    }
}
