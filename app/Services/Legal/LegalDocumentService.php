<?php

namespace App\Services\Legal;

use App\Models\LegalDocument;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LegalDocumentService
{
    /**
     * Return currently active documents used in PIN setup flow.
     *
     * @return array{terms: LegalDocument|null, privacy: LegalDocument|null}
     */
    public function currentDocumentsForPinSetup(): array
    {
        return [
            'terms' => $this->currentActiveByType(LegalDocument::TYPE_TERMS),
            'privacy' => $this->currentActiveByType(LegalDocument::TYPE_PRIVACY),
        ];
    }

    /**
     * Fetch currently active and valid document by type.
     */
    public function currentActiveByType(string $type): ?LegalDocument
    {
        $this->ensureValidType($type);

        return LegalDocument::query()
            ->ofType($type)
            ->currentActive()
            ->orderByDesc('activated_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Create a new legal document version and optionally activate it.
     */
    public function createVersion(
        string $type,
        string $version,
        string $title,
        string $content,
        ?CarbonInterface $effectiveAt = null,
        ?CarbonInterface $expiresAt = null,
        bool $activate = true,
        ?User $actor = null,
    ): LegalDocument {
        $this->ensureValidType($type);
        $this->ensureDateRangeIsValid($effectiveAt, $expiresAt);

        return DB::transaction(function () use (
            $type,
            $version,
            $title,
            $content,
            $effectiveAt,
            $expiresAt,
            $activate,
            $actor
        ) {
            $document = LegalDocument::query()->create([
                'type' => $type,
                'version' => $version,
                'title' => $title,
                'content' => $content,
                'effective_at' => $effectiveAt,
                'expires_at' => $expiresAt,
                'is_active' => false,
                'created_by' => $actor?->id,
            ]);

            if (! $activate) {
                return $document->fresh();
            }

            return $this->activateInTransaction($document, $actor);
        });
    }

    /**
     * Activate an existing legal document version.
     */
    public function activateVersion(LegalDocument $document, ?User $actor = null): LegalDocument
    {
        return DB::transaction(fn () => $this->activateInTransaction($document, $actor));
    }

    /**
     * Deactivate a legal document version.
     */
    public function deactivateVersion(LegalDocument $document): LegalDocument
    {
        $now = now();

        $document->forceFill([
            'is_active' => false,
            'deactivated_at' => $document->deactivated_at ?? $now,
        ])->save();

        return $document->fresh();
    }

    /**
     * Ensure this row is the only active row for its type.
     */
    private function activateInTransaction(LegalDocument $document, ?User $actor = null): LegalDocument
    {
        $document = LegalDocument::query()
            ->lockForUpdate()
            ->findOrFail($document->id);

        if ($document->expires_at && $document->expires_at->isPast()) {
            throw new InvalidArgumentException('No puedes activar una version ya vencida.');
        }

        if ($document->effective_at && $document->effective_at->isFuture()) {
            throw new InvalidArgumentException('No puedes activar una version con vigencia futura.');
        }

        $now = now();

        LegalDocument::query()
            ->where('type', $document->type)
            ->where('id', '!=', $document->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'deactivated_at' => $now,
                'updated_at' => $now,
            ]);

        $document->forceFill([
            'is_active' => true,
            'effective_at' => $document->effective_at ?? $now,
            'activated_at' => $now,
            'deactivated_at' => null,
            'activated_by' => $actor?->id,
        ])->save();

        return $document->fresh();
    }

    /**
     * Ensure the given legal document type is supported.
     */
    private function ensureValidType(string $type): void
    {
        if (! in_array($type, LegalDocument::types(), true)) {
            throw new InvalidArgumentException("Tipo legal invalido: {$type}");
        }
    }

    /**
     * Ensure document date range is coherent.
     */
    private function ensureDateRangeIsValid(?CarbonInterface $effectiveAt, ?CarbonInterface $expiresAt): void
    {
        if (! $effectiveAt || ! $expiresAt) {
            return;
        }

        if ($expiresAt->lt($effectiveAt)) {
            throw new InvalidArgumentException('La fecha de vencimiento no puede ser menor a la fecha de vigencia.');
        }
    }
}
