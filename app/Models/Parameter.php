<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $key
 * @property string $description
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Parameter whereValue($value)
 * @mixin \Eloquent
 */
class Parameter extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'key',
        'description',
        'value',
    ];
}
