<?php

namespace App\Models;

use App\Enums\ExternalServicesType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyExternalService extends Model
{
    protected $fillable = [
        'policy_id',
        'name',
        'date',
        'comments',
        'type',
        'attachment_path',
        'attachment_name',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => ExternalServicesType::class,
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
