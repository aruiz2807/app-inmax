<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserPinSetupToken;
use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PinSetupTokenService
{
    public const PURPOSE_ACTIVATION = 'activation';
    public const PURPOSE_RESET = 'reset';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_INVALID = 'invalid';

    public function __construct(
        private readonly WhatsAppCloudApiService $whatsAppService
    ) {
    }

    /**
     * Generate a setup link and attempt WhatsApp template delivery.
     *
     * @return array{
     *     url: string,
     *     expires_at: Carbon,
     *     whatsapp: array{attempted: bool, ok: bool, reason?: string, status?: int}
     * }
     */
    public function generateSetupLink(
        User $user,
        ?User $createdBy = null,
        string $purpose = self::PURPOSE_ACTIVATION
    ): array
    {
        $token = $this->createToken($user, $createdBy);
        $url = route('pin.setup', ['token' => $token['plain_text_token']]);
        $whatsAppDelivery = $this->sendWhatsAppTemplate(
            user: $user,
            plainTextToken: $token['plain_text_token'],
            purpose: $purpose
        );

        Log::info('WHATSAPP_SIM_PIN_SETUP', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'url' => $url,
            'purpose' => $purpose,
            'expires_at' => $token['expires_at']->toDateTimeString(),
            'whatsapp' => $whatsAppDelivery,
        ]);

        return [
            'url' => $url,
            'expires_at' => $token['expires_at'],
            'whatsapp' => $whatsAppDelivery,
        ];
    }

    /**
     * Resolve a valid and active setup token.
     */
    public function resolveActiveToken(string $plainTextToken): ?UserPinSetupToken
    {
        $resolved = $this->resolveTokenStatus($plainTextToken);

        return $resolved['status'] === self::STATUS_ACTIVE
            ? $resolved['token']
            : null;
    }

    /**
     * Resolve token and identify its current status.
     *
     * @return array{token: UserPinSetupToken|null, status: string}
     */
    public function resolveTokenStatus(string $plainTextToken): array
    {
        $token = UserPinSetupToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (! $token) {
            return [
                'token' => null,
                'status' => self::STATUS_INVALID,
            ];
        }

        if ($token->used_at !== null) {
            return [
                'token' => $token,
                'status' => self::STATUS_USED,
            ];
        }

        if ($token->expires_at->isPast()) {
            return [
                'token' => $token,
                'status' => self::STATUS_EXPIRED,
            ];
        }

        return [
            'token' => $token,
            'status' => self::STATUS_ACTIVE,
        ];
    }

    /**
     * Consume the setup token and persist the new user pin.
     */
    public function consumeToken(UserPinSetupToken $token, string $pin): void
    {
        DB::transaction(function () use ($token, $pin) {
            $user = $token->user()->lockForUpdate()->firstOrFail();
            $now = now();

            $user->forceFill([
                'pin' => $pin,
                'pin_set_at' => $now,
                'phone_verified_at' => $user->phone_verified_at ?? $now,
            ])->save();

            UserPinSetupToken::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => $now]);
        });
    }

    /**
     * Create a new setup token and invalidate previous active tokens for the user.
     *
     * @return array{plain_text_token: string, expires_at: Carbon}
     */
    private function createToken(User $user, ?User $createdBy = null): array
    {
        $now = now();
        $expiresAt = $now->copy()->addMinutes(config('auth.pin_setup.ttl', 30));
        $plainTextToken = Str::random(64);

        DB::transaction(function () use ($user, $createdBy, $now, $expiresAt, $plainTextToken) {
            UserPinSetupToken::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => $now]);

            UserPinSetupToken::create([
                'user_id' => $user->id,
                'created_by' => $createdBy?->id,
                'token_hash' => hash('sha256', $plainTextToken),
                'expires_at' => $expiresAt,
            ]);
        });

        return [
            'plain_text_token' => $plainTextToken,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Attempt to deliver setup/reset link via WhatsApp template configured by admins.
     *
     * @return array{attempted: bool, ok: bool, reason?: string, status?: int}
     */
    private function sendWhatsAppTemplate(User $user, string $plainTextToken, string $purpose): array
    {
        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_api_credentials',
            ];
        }

        $templateName = match ($purpose) {
            self::PURPOSE_RESET => $setting->pin_reset_template_name,
            default => $setting->activation_template_name,
        };

        if (! filled($templateName)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_template_name',
            ];
        }

        $languageCode = $setting->default_language ?: 'es_MX';

        $response = $this->whatsAppService->sendTemplateMessage(
            setting: $setting,
            to: $user->phone,
            templateName: $templateName,
            languageCode: $languageCode,
            parameters: [$user->name],
            buttonUrlParameters: [$plainTextToken]
        );

        return [
            'attempted' => true,
            'ok' => $response['ok'],
            'status' => $response['status'],
        ];
    }
}
