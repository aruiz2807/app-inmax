<?php

namespace App\Livewire\Settings;

use App\Models\LegalDocument;
use App\Services\Legal\LegalDocumentService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class LegalSettingsPage extends Component
{
    public string $termsVersion = '';

    public string $termsTitle = 'Terminos y condiciones';

    public string $termsContent = '';

    public ?string $termsEffectiveAt = null;

    public ?string $termsExpiresAt = null;

    public bool $termsActivate = true;

    public string $privacyVersion = '';

    public string $privacyTitle = 'Aviso de privacidad';

    public string $privacyContent = '';

    public ?string $privacyEffectiveAt = null;

    public ?string $privacyExpiresAt = null;

    public bool $privacyActivate = true;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.settings.legal-settings-page', [
            'termsDocuments' => LegalDocument::query()
                ->ofType(LegalDocument::TYPE_TERMS)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(),
            'privacyDocuments' => LegalDocument::query()
                ->ofType(LegalDocument::TYPE_PRIVACY)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function saveTerms(LegalDocumentService $service): void
    {
        $validated = Validator::make([
            'termsVersion' => $this->termsVersion,
            'termsTitle' => $this->termsTitle,
            'termsContent' => $this->termsContent,
            'termsEffectiveAt' => $this->termsEffectiveAt,
            'termsExpiresAt' => $this->termsExpiresAt,
        ], [
            'termsVersion' => ['required', 'string', 'max:50'],
            'termsTitle' => ['required', 'string', 'max:255'],
            'termsContent' => ['required', 'string', 'min:20'],
            'termsEffectiveAt' => ['nullable', 'date'],
            'termsExpiresAt' => ['nullable', 'date', 'after_or_equal:termsEffectiveAt'],
        ], [
            'termsVersion.required' => 'La version de terminos es obligatoria.',
            'termsContent.min' => 'El contenido debe tener al menos 20 caracteres.',
            'termsExpiresAt.after_or_equal' => 'El vencimiento no puede ser anterior a la vigencia.',
        ])->validate();

        $this->storeDocument(
            service: $service,
            type: LegalDocument::TYPE_TERMS,
            version: $validated['termsVersion'],
            title: $validated['termsTitle'],
            content: $validated['termsContent'],
            effectiveAt: $this->parseDateTime($validated['termsEffectiveAt'] ?? null),
            expiresAt: $this->parseDateTime($validated['termsExpiresAt'] ?? null),
            activate: $this->termsActivate,
            duplicateField: 'termsVersion',
            successMessage: 'Version de terminos guardada correctamente.'
        );

        $this->resetTermsForm();
    }

    public function savePrivacy(LegalDocumentService $service): void
    {
        $validated = Validator::make([
            'privacyVersion' => $this->privacyVersion,
            'privacyTitle' => $this->privacyTitle,
            'privacyContent' => $this->privacyContent,
            'privacyEffectiveAt' => $this->privacyEffectiveAt,
            'privacyExpiresAt' => $this->privacyExpiresAt,
        ], [
            'privacyVersion' => ['required', 'string', 'max:50'],
            'privacyTitle' => ['required', 'string', 'max:255'],
            'privacyContent' => ['required', 'string', 'min:20'],
            'privacyEffectiveAt' => ['nullable', 'date'],
            'privacyExpiresAt' => ['nullable', 'date', 'after_or_equal:privacyEffectiveAt'],
        ], [
            'privacyVersion.required' => 'La version de aviso es obligatoria.',
            'privacyContent.min' => 'El contenido debe tener al menos 20 caracteres.',
            'privacyExpiresAt.after_or_equal' => 'El vencimiento no puede ser anterior a la vigencia.',
        ])->validate();

        $this->storeDocument(
            service: $service,
            type: LegalDocument::TYPE_PRIVACY,
            version: $validated['privacyVersion'],
            title: $validated['privacyTitle'],
            content: $validated['privacyContent'],
            effectiveAt: $this->parseDateTime($validated['privacyEffectiveAt'] ?? null),
            expiresAt: $this->parseDateTime($validated['privacyExpiresAt'] ?? null),
            activate: $this->privacyActivate,
            duplicateField: 'privacyVersion',
            successMessage: 'Version de aviso de privacidad guardada correctamente.'
        );

        $this->resetPrivacyForm();
    }

    public function activateDocument(int $documentId, LegalDocumentService $service): void
    {
        $document = LegalDocument::query()->findOrFail($documentId);

        try {
            $service->activateVersion($document, Auth::user());
        } catch (InvalidArgumentException $exception) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: $exception->getMessage(),
                duration: 5000
            );

            return;
        }

        $label = $document->type === LegalDocument::TYPE_TERMS
            ? 'Terminos y condiciones'
            : 'Aviso de privacidad';

        $this->dispatch(
            'notify',
            type: 'success',
            content: "{$label} activado en version {$document->version}.",
            duration: 4000
        );
    }

    /**
     * Persist one legal document version.
     */
    private function storeDocument(
        LegalDocumentService $service,
        string $type,
        string $version,
        string $title,
        string $content,
        ?Carbon $effectiveAt,
        ?Carbon $expiresAt,
        bool $activate,
        string $duplicateField,
        string $successMessage
    ): void {
        try {
            $service->createVersion(
                type: $type,
                version: trim($version),
                title: trim($title),
                content: trim($content),
                effectiveAt: $effectiveAt,
                expiresAt: $expiresAt,
                activate: $activate,
                actor: Auth::user()
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                $duplicateField => $exception->getMessage(),
            ]);
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'Duplicate entry')) {
                throw ValidationException::withMessages([
                    $duplicateField => 'Esta version ya existe para este tipo de documento.',
                ]);
            }

            throw $exception;
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: $successMessage,
            duration: 4000
        );
    }

    /**
     * Parse incoming datetime-local value.
     */
    private function parseDateTime(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value);
    }

    private function resetTermsForm(): void
    {
        $this->termsVersion = '';
        $this->termsContent = '';
        $this->termsEffectiveAt = null;
        $this->termsExpiresAt = null;
        $this->termsActivate = true;
    }

    private function resetPrivacyForm(): void
    {
        $this->privacyVersion = '';
        $this->privacyContent = '';
        $this->privacyEffectiveAt = null;
        $this->privacyExpiresAt = null;
        $this->privacyActivate = true;
    }
}
