<?php

namespace App\Models;

use App\Enums\ExternalServicesType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property string $name
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $comments
 * @property ExternalServicesType $type
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Policy $policy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService wherePolicyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyExternalService whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
