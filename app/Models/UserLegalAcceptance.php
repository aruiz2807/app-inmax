<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $acceptance_code
 * @property int $user_id
 * @property int|null $user_pin_setup_token_id
 * @property int|null $terms_document_id
 * @property int|null $privacy_document_id
 * @property string $terms_version
 * @property string $privacy_version
 * @property bool $accepted_terms
 * @property bool $accepted_privacy
 * @property bool $accepted_sensitive_data
 * @property \Illuminate\Support\Carbon $accepted_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserPinSetupToken|null $pinSetupToken
 * @property-read \App\Models\LegalDocument|null $privacyDocument
 * @property-read \App\Models\LegalDocument|null $termsDocument
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereAcceptanceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereAcceptedPrivacy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereAcceptedSensitiveData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereAcceptedTerms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance wherePrivacyDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance wherePrivacyVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereTermsDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereTermsVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserLegalAcceptance whereUserPinSetupTokenId($value)
 * @mixin \Eloquent
 */
class UserLegalAcceptance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'acceptance_code',
        'user_id',
        'user_pin_setup_token_id',
        'terms_document_id',
        'privacy_document_id',
        'terms_version',
        'privacy_version',
        'accepted_terms',
        'accepted_privacy',
        'accepted_sensitive_data',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_terms' => 'boolean',
            'accepted_privacy' => 'boolean',
            'accepted_sensitive_data' => 'boolean',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * User that accepted legal documents.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Setup/reset token used during acceptance.
     */
    public function pinSetupToken(): BelongsTo
    {
        return $this->belongsTo(UserPinSetupToken::class, 'user_pin_setup_token_id');
    }

    /**
     * Accepted terms document version.
     */
    public function termsDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class, 'terms_document_id');
    }

    /**
     * Accepted privacy notice version.
     */
    public function privacyDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class, 'privacy_document_id');
    }
}
