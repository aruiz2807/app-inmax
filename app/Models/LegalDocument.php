<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocument extends Model
{
    public const TYPE_TERMS = 'terms';
    public const TYPE_PRIVACY = 'privacy';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'version',
        'title',
        'content',
        'is_active',
        'effective_at',
        'expires_at',
        'activated_at',
        'deactivated_at',
        'created_by',
        'activated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_at' => 'datetime',
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    /**
     * Scope query by legal document type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope documents that are active and currently valid by date range.
     */
    public function scopeCurrentActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('effective_at')
                    ->orWhere('effective_at', '<=', $now);
            })
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * User that created this document version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User that activated this document version.
     */
    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Get supported document types.
     *
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_TERMS,
            self::TYPE_PRIVACY,
        ];
    }
}
