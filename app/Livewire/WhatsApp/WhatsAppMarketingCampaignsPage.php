<?php

namespace App\Livewire\WhatsApp;

use App\Jobs\WhatsApp\SendMarketingCampaignRecipientJob;
use App\Models\WhatsAppConsoleTemplate;
use App\Models\WhatsAppMarketingCampaign;
use App\Models\WhatsAppMarketingCampaignRecipient;
use App\Services\WhatsApp\WhatsAppConsoleTemplateVariableResolver;
use App\Services\WhatsApp\WhatsAppContactService;
use App\Services\WhatsApp\WhatsAppMarketingCampaignImportService;
use App\Services\WhatsApp\WhatsAppMarketingVariableResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class WhatsAppMarketingCampaignsPage extends Component
{
    use WithFileUploads;

    public ?int $campaignId = null;
    public string $name = '';
    public string $description = '';
    public ?int $templateId = null;
    public mixed $recipientsFile = null;
    public mixed $headerMediaFile = null;
    public string $headerMediaSource = 'none';
    public string $headerMediaUrl = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $bodyMappings = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $buttonMappings = [];

    /**
     * @var array<int, array{key: string, label: string}>
     */
    public array $importColumns = [];

    /**
     * @var array<int, array{row_number: int, data: array<string, string>}>
     */
    public array $previewRows = [];

    /**
     * @var array<int, string>
     */
    public array $importErrors = [];

    /**
     * @var array<string, string>
     */
    public array $systemOptions = [];

    public function mount(WhatsAppConsoleTemplateVariableResolver $resolver): void
    {
        $this->systemOptions = $resolver->systemOptions();
        $this->importColumns = [
            ['key' => 'A', 'label' => 'Columna A - Nombre'],
            ['key' => 'B', 'label' => 'Columna B - WhatsApp'],
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $campaigns = WhatsAppMarketingCampaign::query()
            ->with('template', 'createdBy')
            ->withCount('recipients')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $selectedCampaign = $this->campaignId
            ? WhatsAppMarketingCampaign::query()
                ->with('template', 'createdBy')
                ->withCount([
                    'recipients',
                    'recipients as responded_recipients_count' => fn ($query) => $query->whereNotNull('responded_at'),
                    'recipients as button_responses_count' => fn ($query) => $query->whereIn('response_type', ['button', 'interactive']),
                    'recipients as direct_responses_count' => fn ($query) => $query
                        ->whereNotNull('responded_at')
                        ->whereNotIn('response_type', ['button', 'interactive']),
                ])
                ->find($this->campaignId)
            : null;

        $selectedRecipients = $selectedCampaign
            ? $selectedCampaign->recipients()
                ->orderByRaw('responded_at IS NULL')
                ->orderByDesc('responded_at')
                ->orderByDesc('id')
                ->limit(100)
                ->get()
            : collect();

        return view('livewire.whatsapp.marketing-campaigns-page', [
            'campaigns' => $campaigns,
            'templates' => WhatsAppConsoleTemplate::query()
                ->where('is_active', true)
                ->where('allow_marketing', true)
                ->orderBy('name')
                ->get(),
            'selectedCampaign' => $selectedCampaign,
            'selectedRecipients' => $selectedRecipients,
            'selectedTemplate' => $this->selectedTemplate(),
        ]);
    }

    public function updatedTemplateId(): void
    {
        $this->initializeMappings();
    }

    public function updatedRecipientsFile(): void
    {
        $this->validateOnly('recipientsFile', [
            'recipientsFile' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
        ]);

        $result = app(WhatsAppMarketingCampaignImportService::class)->read($this->recipientsFile, 25);
        $this->importColumns = $result['columns'];
        $this->previewRows = array_slice($result['rows'], 0, 12);
        $this->importErrors = $result['errors'];

        if ($this->templateId) {
            $this->initializeMappings(keepExisting: true);
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'description',
            'templateId',
            'recipientsFile',
            'headerMediaFile',
            'headerMediaSource',
            'headerMediaUrl',
            'bodyMappings',
            'buttonMappings',
            'previewRows',
            'importErrors',
        ]);

        $this->headerMediaSource = 'none';
        $this->importColumns = [
            ['key' => 'A', 'label' => 'Columna A - Nombre'],
            ['key' => 'B', 'label' => 'Columna B - WhatsApp'],
        ];
    }

    public function createCampaign(
        WhatsAppMarketingCampaignImportService $importer,
        WhatsAppMarketingVariableResolver $variableResolver,
        WhatsAppContactService $contactService
    ): void {
        $template = $this->selectedTemplate();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'templateId' => [
                'required',
                Rule::exists('whatsapp_console_templates', 'id')->where('is_active', true)->where('allow_marketing', true),
            ],
            'recipientsFile' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
            'headerMediaSource' => ['required', Rule::in(['none', 'file', 'url'])],
            'headerMediaUrl' => ['nullable', 'url', 'max:2000'],
            'bodyMappings' => ['array'],
            'buttonMappings' => ['array'],
        ]);

        if (! $template) {
            $this->addError('templateId', 'Selecciona una plantilla valida.');
            return;
        }

        $this->validateHeaderRequirement($template);
        $this->validateVariableMappings($template);

        $import = $importer->read($this->recipientsFile);

        if ($import['errors'] !== []) {
            $this->importErrors = $import['errors'];
            $this->addError('recipientsFile', implode(' ', array_slice($import['errors'], 0, 5)));
            return;
        }

        [$sourcePath, $sourceName] = $this->storeSourceFile();
        [$headerPath, $headerName, $headerMime] = $this->storeHeaderFile($template);
        $seenPhones = [];
        $invalidCount = 0;
        $validCount = 0;

        $campaign = DB::transaction(function () use (
            $template,
            $import,
            $sourcePath,
            $sourceName,
            $headerPath,
            $headerName,
            $headerMime,
            &$seenPhones,
            &$invalidCount,
            &$validCount,
            $contactService,
            $variableResolver
        ): WhatsAppMarketingCampaign {
            $campaign = WhatsAppMarketingCampaign::query()->create([
                'whatsapp_console_template_id' => $template->id,
                'created_by_user_id' => auth()->id(),
                'name' => trim($this->name),
                'description' => trim($this->description) ?: null,
                'status' => WhatsAppMarketingCampaign::STATUS_DRAFT,
                'body_mappings' => $this->bodyMappings,
                'button_mappings' => $this->buttonMappings,
                'header_media_type' => $template->header_media_type,
                'header_media_source' => $template->header_media_type ? $this->headerMediaSource : 'none',
                'header_media_url' => $this->headerMediaSource === 'url' ? trim($this->headerMediaUrl) : null,
                'header_media_disk' => $headerPath ? 'local' : null,
                'header_media_path' => $headerPath,
                'header_media_name' => $headerName,
                'header_media_mime' => $headerMime,
                'source_file_disk' => 'local',
                'source_file_path' => $sourcePath,
                'source_file_name' => $sourceName,
                'total_recipients' => count($import['rows']),
            ]);

            foreach ($import['rows'] as $row) {
                $rowData = $row['data'];
                $phoneRaw = trim((string) ($rowData['B'] ?? ''));
                $normalizedPhone = $contactService->canonicalPhone($phoneRaw);

                if ($normalizedPhone === '' || strlen($normalizedPhone) < 8 || isset($seenPhones[$normalizedPhone])) {
                    $invalidCount++;
                    continue;
                }

                $seenPhones[$normalizedPhone] = true;
                $recipient = new WhatsAppMarketingCampaignRecipient([
                    'whatsapp_marketing_campaign_id' => $campaign->id,
                    'row_number' => $row['row_number'],
                    'customer_name' => trim((string) ($rowData['A'] ?? '')) ?: null,
                    'phone_raw' => $phoneRaw,
                    'phone_normalized' => $normalizedPhone,
                    'row_data' => $rowData,
                    'status' => WhatsAppMarketingCampaignRecipient::STATUS_PENDING,
                ]);

                $recipient->body_values = $variableResolver->resolve($recipient, $this->bodyMappings);
                $recipient->button_values = $variableResolver->resolve($recipient, $this->buttonMappings);
                $recipient->save();
                $validCount++;
            }

            $campaign->forceFill([
                'valid_recipients' => $validCount,
                'invalid_recipients' => $invalidCount,
            ])->save();

            return $campaign;
        });

        $this->campaignId = $campaign->id;
        $this->resetForm();

        $this->dispatch('close-whatsapp-marketing-campaign-modal');
        $this->dispatch('notify', type: 'success', content: 'Campaña creada en borrador.', duration: 4000);
    }

    public function sendCampaign(int $campaignId, bool $onlyFailed = false): void
    {
        $campaign = WhatsAppMarketingCampaign::query()->with('template')->findOrFail($campaignId);

        if (! $campaign->template?->is_active || ! $campaign->template?->allow_marketing) {
            $this->dispatch('notify', type: 'error', content: 'La plantilla ya no esta activa para mercadotecnia.', duration: 6000);
            return;
        }

        if ($campaign->template->header_media_type && $campaign->header_media_source === 'none') {
            $this->dispatch('notify', type: 'error', content: 'La plantilla requiere encabezado multimedia.', duration: 6000);
            return;
        }

        $query = $campaign->recipients();

        if ($onlyFailed) {
            $query->where('status', WhatsAppMarketingCampaignRecipient::STATUS_FAILED);
        } else {
            $query->where('status', WhatsAppMarketingCampaignRecipient::STATUS_PENDING);
        }

        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            $this->dispatch('notify', type: 'warning', content: 'No hay destinatarios para enviar.', duration: 5000);
            return;
        }

        $campaign->forceFill([
            'status' => WhatsAppMarketingCampaign::STATUS_QUEUED,
            'queued_at' => now(),
            'completed_at' => null,
        ])->save();

        foreach ($recipients->values() as $index => $recipient) {
            SendMarketingCampaignRecipientJob::dispatch($recipient->id, auth()->id())
                ->delay(now()->addSeconds($index * 2));
        }

        $this->dispatch('notify', type: 'success', content: $recipients->count().' mensaje(s) programados.', duration: 4000);
    }

    public function selectCampaign(int $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function closeCampaignDetail(): void
    {
        $this->campaignId = null;
    }

    private function selectedTemplate(): ?WhatsAppConsoleTemplate
    {
        return $this->templateId
            ? WhatsAppConsoleTemplate::query()->find($this->templateId)
            : null;
    }

    private function initializeMappings(bool $keepExisting = false): void
    {
        $template = $this->selectedTemplate();

        if (! $template) {
            $this->bodyMappings = [];
            $this->buttonMappings = [];
            return;
        }

        $this->bodyMappings = $this->buildMappings($template->body_variables ?? [], $keepExisting ? $this->bodyMappings : []);
        $this->buttonMappings = $this->buildMappings($template->button_variables ?? [], $keepExisting ? $this->buttonMappings : []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     * @param  array<int, array<string, mixed>>  $existing
     * @return array<int, array<string, mixed>>
     */
    private function buildMappings(array $variables, array $existing = []): array
    {
        return collect(array_values($variables))
            ->map(function (array $variable, int $index) use ($existing): array {
                if (isset($existing[$index])) {
                    return $existing[$index];
                }

                $label = str((string) ($variable['label'] ?? ''))->lower()->ascii()->value();
                $sourceType = ($variable['source_type'] ?? '') === 'system' ? 'system' : 'column';

                return [
                    'label' => (string) ($variable['label'] ?? 'Variable '.($index + 1)),
                    'source_type' => $sourceType,
                    'column_key' => str_contains($label, 'nombre') || str_contains($label, 'name') ? 'A' : '',
                    'system_key' => $sourceType === 'system' ? (string) ($variable['system_key'] ?? '') : '',
                    'static_value' => '',
                    'required' => (bool) ($variable['required'] ?? true),
                ];
            })
            ->values()
            ->all();
    }

    private function validateHeaderRequirement(WhatsAppConsoleTemplate $template): void
    {
        if (! $template->header_media_type) {
            return;
        }

        if ($this->headerMediaSource === 'none') {
            $this->addError('headerMediaSource', 'Esta plantilla requiere encabezado multimedia.');
            throw \Illuminate\Validation\ValidationException::withMessages([
                'headerMediaSource' => 'Esta plantilla requiere encabezado multimedia.',
            ]);
        }

        if ($this->headerMediaSource === 'url') {
            if ($template->header_media_type !== 'image') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'headerMediaUrl' => 'Por URL solo se permite encabezado de imagen.',
                ]);
            }

            if (blank($this->headerMediaUrl)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'headerMediaUrl' => 'Captura la URL publica de la imagen.',
                ]);
            }

            return;
        }

        if (! $this->headerMediaFile) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'headerMediaFile' => 'Carga el archivo del encabezado.',
            ]);
        }

        $mime = (string) $this->headerMediaFile->getMimeType();
        $valid = match ($template->header_media_type) {
            'image' => in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true),
            'video' => in_array($mime, ['video/mp4', 'video/3gpp', 'video/quicktime'], true),
            'document' => $mime === 'application/pdf',
            default => false,
        };

        if (! $valid) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'headerMediaFile' => 'El archivo no coincide con el encabezado configurado.',
            ]);
        }
    }

    private function validateVariableMappings(WhatsAppConsoleTemplate $template): void
    {
        foreach ([
            'bodyMappings' => $this->bodyMappings,
            'buttonMappings' => $this->buttonMappings,
        ] as $property => $mappings) {
            foreach ($mappings as $index => $mapping) {
                $sourceType = (string) ($mapping['source_type'] ?? '');

                if (! in_array($sourceType, ['column', 'system', 'static'], true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "{$property}.{$index}.source_type" => 'Selecciona el origen de la variable.',
                    ]);
                }

                if ($sourceType === 'column' && blank($mapping['column_key'] ?? null)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "{$property}.{$index}.column_key" => 'Selecciona la columna.',
                    ]);
                }

                if ($sourceType === 'system' && blank($mapping['system_key'] ?? null)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "{$property}.{$index}.system_key" => 'Selecciona el campo del sistema.',
                    ]);
                }
            }
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function storeSourceFile(): array
    {
        $originalName = $this->recipientsFile->getClientOriginalName();
        $path = $this->recipientsFile->storeAs(
            'whatsapp/marketing/imports/'.now()->format('Y/m/d'),
            Str::uuid().'_'.$originalName,
            'local'
        );

        return [$path, $originalName];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function storeHeaderFile(WhatsAppConsoleTemplate $template): array
    {
        if (! $template->header_media_type || $this->headerMediaSource !== 'file' || ! $this->headerMediaFile) {
            return [null, null, null];
        }

        $originalName = $this->headerMediaFile->getClientOriginalName();
        $path = $this->headerMediaFile->storeAs(
            'whatsapp/marketing/headers/'.now()->format('Y/m/d'),
            Str::uuid().'_'.$originalName,
            'local'
        );

        return [$path, $originalName, $this->headerMediaFile->getMimeType()];
    }
}
