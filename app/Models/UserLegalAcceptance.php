<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
