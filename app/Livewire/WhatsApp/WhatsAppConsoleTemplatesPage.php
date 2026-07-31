<?php

namespace App\Livewire\WhatsApp;

use App\Models\WhatsAppConsoleTemplate;
use App\Services\WhatsApp\WhatsAppConsoleTemplateVariableResolver;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class WhatsAppConsoleTemplatesPage extends Component
{
    public ?int $templateId = null;
    public string $name = '';
    public string $metaTemplateName = '';
    public string $languageCode = 'es_MX';
    public string $exampleText = '';
    public string $headerMediaType = '';
    public bool $isActive = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $bodyVariables = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $buttonVariables = [];

    /**
     * @var array<string, string>
     */
    public array $systemOptions = [];

    public function mount(WhatsAppConsoleTemplateVariableResolver $resolver): void
    {
        $this->systemOptions = $resolver->systemOptions();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.whatsapp.console-templates-page', [
            'templates' => WhatsAppConsoleTemplate::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    #[On('editWhatsAppConsoleTemplate')]
    public function edit(int $templateId): void
    {
        $template = WhatsAppConsoleTemplate::query()->findOrFail($templateId);

        $this->templateId = $template->id;
        $this->name = $template->name;
        $this->metaTemplateName = $template->meta_template_name;
        $this->languageCode = $template->language_code;
        $this->exampleText = $template->example_text ?? '';
        $this->headerMediaType = $template->header_media_type ?? '';
        $this->isActive = (bool) $template->is_active;
        $this->bodyVariables = $this->normalizeVariables($template->body_variables ?? []);
        $this->buttonVariables = $this->normalizeVariables($template->button_variables ?? []);

        $this->dispatch('open-whatsapp-console-template-modal');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'metaTemplateName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('whatsapp_console_templates', 'meta_template_name')
                    ->where('language_code', $this->languageCode)
                    ->ignore($this->templateId),
            ],
            'languageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'exampleText' => ['nullable', 'string', 'max:2000'],
            'headerMediaType' => ['nullable', Rule::in(['', 'image', 'video', 'document'])],
            'isActive' => ['boolean'],
            'bodyVariables' => ['nullable', 'array'],
            'bodyVariables.*.label' => ['required', 'string', 'max:120'],
            'bodyVariables.*.help_text' => ['nullable', 'string', 'max:255'],
            'bodyVariables.*.source_type' => ['required', Rule::in(['custom', 'system'])],
            'bodyVariables.*.system_key' => ['nullable', 'string', Rule::in(array_keys($this->systemOptions))],
            'bodyVariables.*.example_value' => ['nullable', 'string', 'max:255'],
            'bodyVariables.*.required' => ['boolean'],
            'buttonVariables' => ['nullable', 'array'],
            'buttonVariables.*.label' => ['required', 'string', 'max:120'],
            'buttonVariables.*.help_text' => ['nullable', 'string', 'max:255'],
            'buttonVariables.*.source_type' => ['required', Rule::in(['custom', 'system'])],
            'buttonVariables.*.system_key' => ['nullable', 'string', Rule::in(array_keys($this->systemOptions))],
            'buttonVariables.*.example_value' => ['nullable', 'string', 'max:255'],
            'buttonVariables.*.required' => ['boolean'],
        ], [
            'metaTemplateName.unique' => 'Ya existe una plantilla con ese nombre Meta e idioma.',
            'languageCode.regex' => 'El idioma debe tener formato es o es_MX.',
            '*.label.required' => 'Captura el nombre de la variable.',
        ]);

        $this->validateSystemVariables($validated['bodyVariables'] ?? [], 'bodyVariables');
        $this->validateSystemVariables($validated['buttonVariables'] ?? [], 'buttonVariables');

        WhatsAppConsoleTemplate::query()->updateOrCreate(
            ['id' => $this->templateId],
            [
                'name' => trim($validated['name']),
                'meta_template_name' => trim($validated['metaTemplateName']),
                'language_code' => trim($validated['languageCode']),
                'example_text' => trim($validated['exampleText'] ?? '') ?: null,
                'header_media_type' => filled($validated['headerMediaType'] ?? null) ? $validated['headerMediaType'] : null,
                'body_variables' => $this->prepareVariables($validated['bodyVariables'] ?? []),
                'button_variables' => $this->prepareVariables($validated['buttonVariables'] ?? []),
                'is_active' => (bool) $validated['isActive'],
            ]
        );

        $this->dispatch(
            'notify',
            type: 'success',
            content: $this->templateId ? 'Plantilla actualizada correctamente.' : 'Plantilla creada correctamente.',
            duration: 4000
        );

        $this->dispatch('close-whatsapp-console-template-modal');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'templateId',
            'name',
            'metaTemplateName',
            'exampleText',
            'headerMediaType',
            'bodyVariables',
            'buttonVariables',
        ]);

        $this->languageCode = 'es_MX';
        $this->isActive = true;
    }

    public function addBodyVariable(): void
    {
        $this->bodyVariables[] = $this->defaultVariable();
    }

    public function addButtonVariable(): void
    {
        $this->buttonVariables[] = $this->defaultVariable('Parametro boton');
    }

    public function removeBodyVariable(int $index): void
    {
        unset($this->bodyVariables[$index]);
        $this->bodyVariables = array_values($this->bodyVariables);
    }

    public function removeButtonVariable(int $index): void
    {
        unset($this->buttonVariables[$index]);
        $this->buttonVariables = array_values($this->buttonVariables);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultVariable(string $label = 'Parametro body'): array
    {
        return [
            'label' => $label,
            'help_text' => '',
            'source_type' => 'custom',
            'system_key' => '',
            'example_value' => '',
            'required' => true,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     * @return array<int, array<string, mixed>>
     */
    private function normalizeVariables(array $variables): array
    {
        return collect($variables)
            ->map(fn (array $variable): array => array_merge($this->defaultVariable(), $variable))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     * @return array<int, array<string, mixed>>
     */
    private function prepareVariables(array $variables): array
    {
        return collect($variables)
            ->map(function (array $variable): array {
                $sourceType = (string) ($variable['source_type'] ?? 'custom');

                return [
                    'label' => trim((string) ($variable['label'] ?? '')),
                    'help_text' => trim((string) ($variable['help_text'] ?? '')),
                    'source_type' => $sourceType,
                    'system_key' => $sourceType === 'system' ? (string) ($variable['system_key'] ?? '') : '',
                    'example_value' => trim((string) ($variable['example_value'] ?? '')),
                    'required' => (bool) ($variable['required'] ?? true),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     */
    private function validateSystemVariables(array $variables, string $property): void
    {
        $errors = [];

        foreach ($variables as $index => $variable) {
            if (($variable['source_type'] ?? 'custom') === 'system' && blank($variable['system_key'] ?? null)) {
                $errors[$property.'.'.$index.'.system_key'] = 'Selecciona el campo del sistema.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
