<?php

namespace App\Livewire\Auth;

use App\Models\LegalDocument;
use App\Models\User;
use App\Services\Auth\PinSetupTokenService;
use App\Services\Legal\LegalDocumentService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PinSetupPage extends Component
{
    public string $token = '';

    public ?User $user = null;

    public string $tokenStatus = PinSetupTokenService::STATUS_INVALID;

    public ?string $tokenMessage = null;

    public string $pin = '';

    public string $pin_confirmation = '';

    public bool $acceptTerms = false;

    public bool $acceptPrivacy = false;

    public bool $acceptSensitiveData = false;

    public bool $legalReady = false;

    public ?string $legalMessage = null;

    public ?int $termsDocumentId = null;

    public ?int $privacyDocumentId = null;

    public string $termsVersion = '';

    public string $privacyVersion = '';

    public string $termsTitle = '';

    public string $privacyTitle = '';

    public string $termsContent = '';

    public string $privacyContent = '';

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.pin-setup-page');
    }

    public function mount(
        string $token,
        PinSetupTokenService $tokenService,
        LegalDocumentService $legalDocumentService
    ): void {
        $this->token = $token;
        $resolved = $tokenService->resolveTokenStatus($this->token);

        $this->tokenStatus = $resolved['status'];
        $this->user = $resolved['token']?->user;
        $this->tokenMessage = $this->resolveTokenMessage($this->tokenStatus);

        $this->hydrateLegalDocuments($legalDocumentService);
    }

    public function save(PinSetupTokenService $tokenService, LegalDocumentService $legalDocumentService)
    {
        $this->hydrateLegalDocuments($legalDocumentService);

        $resolved = $tokenService->resolveTokenStatus($this->token);
        $this->tokenStatus = $resolved['status'];
        $this->tokenMessage = $this->resolveTokenMessage($this->tokenStatus);

        if ($this->tokenStatus !== PinSetupTokenService::STATUS_ACTIVE || ! $resolved['token']) {
            throw ValidationException::withMessages([
                'pin' => __($this->tokenMessage),
            ]);
        }

        if (! $this->legalReady) {
            throw ValidationException::withMessages([
                'acceptTerms' => __($this->legalMessage ?: 'No hay documentos legales activos para completar el registro.'),
            ]);
        }

        Validator::make([
            'pin' => $this->pin,
            'pin_confirmation' => $this->pin_confirmation,
            'acceptTerms' => $this->acceptTerms,
            'acceptPrivacy' => $this->acceptPrivacy,
            'acceptSensitiveData' => $this->acceptSensitiveData,
        ], [
            'pin' => ['required', 'digits:4', 'confirmed'],
            'pin_confirmation' => ['required', 'digits:4'],
            'acceptTerms' => ['accepted'],
            'acceptPrivacy' => ['accepted'],
            'acceptSensitiveData' => ['accepted'],
        ], [
            'acceptTerms.accepted' => 'Debes aceptar los terminos y condiciones.',
            'acceptPrivacy.accepted' => 'Debes aceptar el aviso de privacidad.',
            'acceptSensitiveData.accepted' => 'Debes confirmar el consentimiento de datos sensibles.',
        ])->validate();

        $tokenService->consumeToken($resolved['token'], $this->pin, [
            'terms_document_id' => $this->termsDocumentId,
            'privacy_document_id' => $this->privacyDocumentId,
            'terms_version' => $this->termsVersion,
            'privacy_version' => $this->privacyVersion,
            'accepted_terms' => $this->acceptTerms,
            'accepted_privacy' => $this->acceptPrivacy,
            'accepted_sensitive_data' => $this->acceptSensitiveData,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        return redirect()
            ->route('login')
            ->with('status', __('PIN configurado correctamente. Inicia sesion con tu telefono y PIN.'));
    }

    public function canSetPin(): bool
    {
        return $this->tokenStatus === PinSetupTokenService::STATUS_ACTIVE;
    }

    private function resolveTokenMessage(string $status): string
    {
        return match ($status) {
            PinSetupTokenService::STATUS_USED => 'Este enlace ya fue usado. Solicita uno nuevo al administrador.',
            PinSetupTokenService::STATUS_EXPIRED => 'Este enlace ya vencio. Solicita uno nuevo al administrador.',
            PinSetupTokenService::STATUS_ACTIVE => '',
            default => 'Este enlace es invalido. Solicita uno nuevo al administrador.',
        };
    }

    private function hydrateLegalDocuments(LegalDocumentService $legalDocumentService): void
    {
        $documents = $legalDocumentService->currentDocumentsForPinSetup();
        $termsDocument = $documents['terms'];
        $privacyDocument = $documents['privacy'];

        $this->termsDocumentId = $termsDocument?->id;
        $this->privacyDocumentId = $privacyDocument?->id;
        $this->termsVersion = $termsDocument?->version ?? '';
        $this->privacyVersion = $privacyDocument?->version ?? '';
        $this->termsTitle = $termsDocument?->title ?? 'Terminos y condiciones';
        $this->privacyTitle = $privacyDocument?->title ?? 'Aviso de privacidad';
        $this->termsContent = $termsDocument?->content ?? '';
        $this->privacyContent = $privacyDocument?->content ?? '';

        $this->legalReady = $termsDocument instanceof LegalDocument
            && $privacyDocument instanceof LegalDocument;

        $this->legalMessage = $this->legalReady
            ? null
            : 'No existe una version activa y vigente de terminos y aviso. Solicita al administrador la configuracion legal.';
    }
}
