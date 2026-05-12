<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $service_id
 * @property int|null $events
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Plan $plan
 * @property-read \App\Models\Service $service
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage whereEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanCoverage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PlanCoverage extends Model
{
    protected $table = 'plan_coverage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
        'service_id',
        'events',
    ];

    /**
     * Each coverage belongs to one plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Each coverage can have or be one kind of service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}