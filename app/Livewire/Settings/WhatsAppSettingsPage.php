<?php

namespace App\Livewire\Settings;

use App\Models\WhatsAppSetting;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppConsoleTemplate;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Services\WhatsApp\WhatsAppTemplateParameterResolver;
use App\Services\WhatsApp\WhatsAppTemplateCreationService;
use App\Services\WhatsApp\WhatsAppTemplateDefinition;
use App\Services\WhatsApp\WhatsAppTemplateSyncService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class WhatsAppSettingsPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    private const PARAMETER_SCOPE_MAP = [
        'systemUserActivationBodyParameters' => WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BODY,
        'systemUserActivationButtonParameters' => WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BUTTON,
        'activationBodyParameters' => WhatsAppTemplateParameterResolver::ACTIVATION_BODY,
        'activationButtonParameters' => WhatsAppTemplateParameterResolver::ACTIVATION_BUTTON,
        'pinResetBodyParameters' => WhatsAppTemplateParameterResolver::PIN_RESET_BODY,
        'pinResetButtonParameters' => WhatsAppTemplateParameterResolver::PIN_RESET_BUTTON,
        'preregistrationBodyParameters' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BODY,
        'preregistrationButtonParameters' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BUTTON,
        'appointmentRequestBodyParameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BODY,
        'appointmentRequestButtonParameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BUTTON,
        'appointmentCompletedBodyParameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BODY,
        'appointmentCompletedButtonParameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BUTTON,
    ];

    public string $apiVersion = 'v22.0';
    public string $phoneNumberId = '';
    public string $businessAccountId = '';
    public string $metaAppId = '';
    public string $accessToken = '';
    public string $webhookVerifyToken = '';
    public string $appSecret = '';
    public bool $webhookEnabled = false;
    public ?string $webhookLastReceivedAt = null;
    public ?string $webhookLastStatus = null;
    public string $systemUserActivationTemplateName = '';
    public string $systemUserActivationLanguageCode = 'es_MX';
    public string $activationTemplateName = '';
    public string $activationLanguageCode = 'es_MX';
    public string $pinResetTemplateName = '';
    public string $pinResetLanguageCode = 'es_MX';
    public string $preregistrationTemplateName = '';
    public string $preregistrationLanguageCode = 'es_MX';
    public string $appointmentRequestTemplateName = '';
    public string $appointmentRequestLanguageCode = 'es_MX';
    public string $appointmentCompletedTemplateName = '';
    public string $appointmentCompletedLanguageCode = 'es_MX';
    public array $systemUserActivationBodyParameters = [];
    public array $systemUserActivationButtonParameters = [];
    public array $activationBodyParameters = [];
    public array $activationButtonParameters = [];
    public array $pinResetBodyParameters = [];
    public array $pinResetButtonParameters = [];
    public array $preregistrationBodyParameters = [];
    public array $preregistrationButtonParameters = [];
    public array $appointmentRequestBodyParameters = [];
    public array $appointmentRequestButtonParameters = [];
    public array $appointmentCompletedBodyParameters = [];
    public array $appointmentCompletedButtonParameters = [];
    public string $defaultLanguage = 'es_MX';
    public bool $hasStoredAccessToken = false;
    public bool $hasStoredWebhookVerifyToken = false;
    public bool $hasStoredAppSecret = false;
    public array $parameterOptions = [];
    public array $templateSections = [];

    public string $testPhone = '';
    public string $testTemplateName = '';
    public string $testLanguageCode = 'es_MX';
    public string $testParameters = '';
    public string $testButtonUrlParameters = '';
    public ?string $lastTestMessageId = null;
    public ?string $lastTestResponse = null;

    public string $newTemplateName = '';
    public string $newTemplateLanguage = 'es_MX';
    public string $newTemplateCategory = 'MARKETING';
    public string $newTemplateHeaderType = 'NONE';
    public string $newTemplateHeaderText = '';
    public string $newTemplateHeaderExamples = '';
    public mixed $newTemplateHeaderSample = null;
    public string $newTemplateBody = '';
    public string $newTemplateBodyExamples = '';
    public string $newTemplateFooter = '';
    public ?int $previewTemplateId = null;
    public int $metaTemplatesPerPage = 10;
    public string $metaTemplateSearch = '';

    #[Layout('layouts.app')]
    public function render()
    {
        $definition = app(WhatsAppTemplateDefinition::class);

        return view('livewire.settings.whatsapp-settings-page', [
            'metaTemplates' => WhatsAppMessageTemplate::query()
                ->when(trim($this->metaTemplateSearch) !== '', function ($query): void {
                    $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($this->metaTemplateSearch)).'%';

                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', $search)
                            ->orWhere('meta_id', 'like', $search)
                            ->orWhere('language_code', 'like', $search)
                            ->orWhere('status', 'like', $search)
                            ->orWhere('category', 'like', $search);
                    });
                })
                ->orderByDesc('last_synced_at')
                ->orderBy('name')
                ->paginate($this->metaTemplatesPerPage, ['*'], 'metaTemplatesPage')
                ->through(function (WhatsAppMessageTemplate $template) use ($definition): array {
                    $requirements = $definition->requirements($template);

                    return [
                        'model' => $template,
                        'requirements' => $requirements,
                        'header_media_type' => $definition->headerMediaType($template),
                        'button_variables' => $this->countButtonVariables($template),
                    ];
                }),
        ]);
    }

    public function mount(): void
    {
        $resolver = app(WhatsAppTemplateParameterResolver::class);
        $this->parameterOptions = $resolver->allOptions();
        $this->templateSections = $this->buildTemplateSections();

        $setting = WhatsAppSetting::query()->first();

        if (! $setting) {
            $this->hydrateDefaultParameterMappings($resolver);
            return;
        }

        $this->apiVersion = $setting->api_version;
        $this->phoneNumberId = $setting->phone_number_id ?? '';
        $this->businessAccountId = $setting->business_account_id ?? '';
        $this->metaAppId = $setting->meta_app_id ?? '';
        $this->webhookVerifyToken = '';
        $this->webhookEnabled = (bool) ($setting->webhook_enabled ?? false);
        $this->webhookLastReceivedAt = $setting->webhook_last_received_at?->format('d/m/Y H:i:s');
        $this->webhookLastStatus = $setting->webhook_last_status;
        $this->systemUserActivationTemplateName = $setting->system_user_activation_template_name ?? '';
        $this->systemUserActivationLanguageCode = $setting->system_user_activation_language_code ?: ($setting->default_language ?: 'es_MX');
        $this->activationTemplateName = $setting->activation_template_name ?? '';
        $this->activationLanguageCode = $setting->activation_language_code ?: ($setting->default_language ?: 'es_MX');
        $this->systemUserActivationBodyParameters = $this->normalizeConfiguredParameters(
            $setting->system_user_activation_body_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BODY)
        );
        $this->systemUserActivationButtonParameters = $this->normalizeConfiguredParameters(
            $setting->system_user_activation_button_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BUTTON)
        );
        $this->pinResetTemplateName = $setting->pin_reset_template_name ?? '';
        $this->pinResetLanguageCode = $setting->pin_reset_language_code ?: ($setting->default_language ?: 'es_MX');
        $this->preregistrationTemplateName = $setting->preregistration_template_name ?? '';
        $this->preregistrationLanguageCode = $setting->preregistration_language_code ?: ($setting->default_language ?: 'es_MX');
        $this->appointmentRequestTemplateName = $setting->appointment_request_template_name ?? '';
        $this->appointmentRequestLanguageCode = $setting->appointment_request_language_code ?: ($setting->default_language ?: 'es_MX');
        $this->appointmentCompletedTemplateName = $setting->appointment_completed_template_name ?? '';
        $this->appointmentCompletedLanguageCode = $setting->appointment_completed_language_code ?: ($setting->default_language ?: 'es_MX');
        $this->activationBodyParameters = $this->normalizeConfiguredParameters(
            $setting->activation_body_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::ACTIVATION_BODY)
        );
        $this->activationButtonParameters = $this->normalizeConfiguredParameters(
            $setting->activation_button_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::ACTIVATION_BUTTON)
        );
        $this->pinResetBodyParameters = $this->normalizeConfiguredParameters(
            $setting->pin_reset_body_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PIN_RESET_BODY)
        );
        $this->pinResetButtonParameters = $this->normalizeConfiguredParameters(
            $setting->pin_reset_button_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PIN_RESET_BUTTON)
        );
        $this->preregistrationBodyParameters = $this->normalizeConfiguredParameters(
            $setting->preregistration_body_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PREREGISTRATION_BODY)
        );
        $this->preregistrationButtonParameters = $this->normalizeConfiguredParameters(
            $setting->preregistration_button_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PREREGISTRATION_BUTTON)
        );
        $this->appointmentRequestBodyParameters = $this->normalizeConfiguredParameters(
            $setting->appointment_request_body_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BODY)
        );
        $this->appointmentRequestButtonParameters = $this->normalizeConfiguredParameters(
            $setting->appointment_request_button_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BUTTON)
        );
        $this->appointmentCompletedBodyParameters = $this->normalizeConfiguredParameters(
            $setting->appointment_completed_body_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BODY)
        );
        $this->appointmentCompletedButtonParameters = $this->normalizeConfiguredParameters(
            $setting->appointment_completed_button_parameters ?? $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BUTTON)
        );
        $this->defaultLanguage = $setting->default_language;
        $this->testLanguageCode = $setting->default_language;
        $this->hasStoredAccessToken = filled($setting->access_token);
        $this->hasStoredWebhookVerifyToken = filled($setting->webhook_verify_token);
        $this->hasStoredAppSecret = filled($setting->app_secret);
    }

    public function updatedMetaTemplateSearch(): void
    {
        $this->resetPage('metaTemplatesPage');
    }

    public function saveSettings(): void
    {
        $resolver = app(WhatsAppTemplateParameterResolver::class);

        $rules = [
            'apiVersion' => ['required', 'regex:/^v\d+\.\d+$/'],
            'phoneNumberId' => ['required', 'digits_between:8,30'],
            'businessAccountId' => ['nullable', 'string', 'max:80'],
            'metaAppId' => ['nullable', 'string', 'max:80'],
            'webhookVerifyToken' => ['nullable', 'string', 'max:255'],
            'appSecret' => ['nullable', 'string', 'min:10'],
            'webhookEnabled' => ['boolean'],
            'systemUserActivationTemplateName' => ['nullable', 'string', 'max:255'],
            'systemUserActivationLanguageCode' => ['required_with:systemUserActivationTemplateName', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'activationTemplateName' => ['required', 'string', 'max:255'],
            'activationLanguageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'pinResetTemplateName' => ['required', 'string', 'max:255'],
            'pinResetLanguageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'preregistrationTemplateName' => ['required', 'string', 'max:255'],
            'preregistrationLanguageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'appointmentRequestTemplateName' => ['required', 'string', 'max:255'],
            'appointmentRequestLanguageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'appointmentCompletedTemplateName' => ['required', 'string', 'max:255'],
            'appointmentCompletedLanguageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'systemUserActivationBodyParameters' => ['nullable', 'array'],
            'systemUserActivationBodyParameters.*' => ['nullable', 'string'],
            'systemUserActivationButtonParameters' => ['nullable', 'array'],
            'systemUserActivationButtonParameters.*' => ['nullable', 'string'],
            'activationBodyParameters' => ['nullable', 'array'],
            'activationBodyParameters.*' => ['nullable', 'string'],
            'activationButtonParameters' => ['nullable', 'array'],
            'activationButtonParameters.*' => ['nullable', 'string'],
            'pinResetBodyParameters' => ['nullable', 'array'],
            'pinResetBodyParameters.*' => ['nullable', 'string'],
            'pinResetButtonParameters' => ['nullable', 'array'],
            'pinResetButtonParameters.*' => ['nullable', 'string'],
            'preregistrationBodyParameters' => ['nullable', 'array'],
            'preregistrationBodyParameters.*' => ['nullable', 'string'],
            'preregistrationButtonParameters' => ['nullable', 'array'],
            'preregistrationButtonParameters.*' => ['nullable', 'string'],
            'appointmentRequestBodyParameters' => ['nullable', 'array'],
            'appointmentRequestBodyParameters.*' => ['nullable', 'string'],
            'appointmentRequestButtonParameters' => ['nullable', 'array'],
            'appointmentRequestButtonParameters.*' => ['nullable', 'string'],
            'appointmentCompletedBodyParameters' => ['nullable', 'array'],
            'appointmentCompletedBodyParameters.*' => ['nullable', 'string'],
            'appointmentCompletedButtonParameters' => ['nullable', 'array'],
            'appointmentCompletedButtonParameters.*' => ['nullable', 'string'],
            'defaultLanguage' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
        ];

        if (! $this->hasStoredAccessToken || filled($this->accessToken)) {
            $rules['accessToken'] = ['required', 'string', 'min:10'];
        }

        if ($this->webhookEnabled && (! $this->hasStoredWebhookVerifyToken || filled($this->webhookVerifyToken))) {
            $rules['webhookVerifyToken'] = ['required', 'string', 'max:255'];
        }

        Validator::make([
            'apiVersion' => $this->apiVersion,
            'phoneNumberId' => $this->phoneNumberId,
            'businessAccountId' => $this->businessAccountId,
            'metaAppId' => $this->metaAppId,
            'accessToken' => $this->accessToken,
            'webhookVerifyToken' => $this->webhookVerifyToken,
            'appSecret' => $this->appSecret,
            'webhookEnabled' => $this->webhookEnabled,
            'systemUserActivationTemplateName' => $this->systemUserActivationTemplateName,
            'systemUserActivationLanguageCode' => $this->systemUserActivationLanguageCode,
            'activationTemplateName' => $this->activationTemplateName,
            'activationLanguageCode' => $this->activationLanguageCode,
            'pinResetTemplateName' => $this->pinResetTemplateName,
            'pinResetLanguageCode' => $this->pinResetLanguageCode,
            'preregistrationTemplateName' => $this->preregistrationTemplateName,
            'preregistrationLanguageCode' => $this->preregistrationLanguageCode,
            'appointmentRequestTemplateName' => $this->appointmentRequestTemplateName,
            'appointmentRequestLanguageCode' => $this->appointmentRequestLanguageCode,
            'appointmentCompletedTemplateName' => $this->appointmentCompletedTemplateName,
            'appointmentCompletedLanguageCode' => $this->appointmentCompletedLanguageCode,
            'systemUserActivationBodyParameters' => $this->systemUserActivationBodyParameters,
            'systemUserActivationButtonParameters' => $this->systemUserActivationButtonParameters,
            'activationBodyParameters' => $this->activationBodyParameters,
            'activationButtonParameters' => $this->activationButtonParameters,
            'pinResetBodyParameters' => $this->pinResetBodyParameters,
            'pinResetButtonParameters' => $this->pinResetButtonParameters,
            'preregistrationBodyParameters' => $this->preregistrationBodyParameters,
            'preregistrationButtonParameters' => $this->preregistrationButtonParameters,
            'appointmentRequestBodyParameters' => $this->appointmentRequestBodyParameters,
            'appointmentRequestButtonParameters' => $this->appointmentRequestButtonParameters,
            'appointmentCompletedBodyParameters' => $this->appointmentCompletedBodyParameters,
            'appointmentCompletedButtonParameters' => $this->appointmentCompletedButtonParameters,
            'defaultLanguage' => $this->defaultLanguage,
        ], $rules, [
            'apiVersion.regex' => 'El formato de version debe ser vNN.N (ej. v22.0).',
            'defaultLanguage.regex' => 'El idioma debe tener formato es o es_MX.',
        ])->validate();

        $mappings = $this->validatedTemplateMappings($resolver);

        $setting = WhatsAppSetting::query()->firstOrNew(['id' => 1]);

        $setting->api_version = $this->apiVersion;
        $setting->phone_number_id = $this->phoneNumberId;
        $setting->business_account_id = trim($this->businessAccountId) ?: null;
        $setting->meta_app_id = trim($this->metaAppId) ?: null;
        $setting->webhook_enabled = $this->webhookEnabled;
        $setting->system_user_activation_template_name = $this->systemUserActivationTemplateName;
        $setting->system_user_activation_language_code = filled($this->systemUserActivationTemplateName)
            ? $this->systemUserActivationLanguageCode
            : null;
        $setting->system_user_activation_body_parameters = $mappings['system_user_activation_body_parameters'];
        $setting->system_user_activation_button_parameters = $mappings['system_user_activation_button_parameters'];
        $setting->activation_template_name = $this->activationTemplateName;
        $setting->activation_language_code = $this->activationLanguageCode;
        $setting->activation_body_parameters = $mappings['activation_body_parameters'];
        $setting->activation_button_parameters = $mappings['activation_button_parameters'];
        $setting->pin_reset_template_name = $this->pinResetTemplateName;
        $setting->pin_reset_language_code = $this->pinResetLanguageCode;
        $setting->pin_reset_body_parameters = $mappings['pin_reset_body_parameters'];
        $setting->pin_reset_button_parameters = $mappings['pin_reset_button_parameters'];
        $setting->preregistration_template_name = $this->preregistrationTemplateName;
        $setting->preregistration_language_code = $this->preregistrationLanguageCode;
        $setting->preregistration_body_parameters = $mappings['preregistration_body_parameters'];
        $setting->preregistration_button_parameters = $mappings['preregistration_button_parameters'];
        $setting->appointment_request_template_name = $this->appointmentRequestTemplateName;
        $setting->appointment_request_language_code = $this->appointmentRequestLanguageCode;
        $setting->appointment_request_body_parameters = $mappings['appointment_request_body_parameters'];
        $setting->appointment_request_button_parameters = $mappings['appointment_request_button_parameters'];
        $setting->appointment_completed_template_name = $this->appointmentCompletedTemplateName;
        $setting->appointment_completed_language_code = $this->appointmentCompletedLanguageCode;
        $setting->appointment_completed_body_parameters = $mappings['appointment_completed_body_parameters'];
        $setting->appointment_completed_button_parameters = $mappings['appointment_completed_button_parameters'];
        $setting->default_language = $this->defaultLanguage;

        if (filled($this->accessToken)) {
            $setting->access_token = $this->accessToken;
            $this->accessToken = '';
            $this->hasStoredAccessToken = true;
        }

        if (filled($this->webhookVerifyToken)) {
            $setting->webhook_verify_token = $this->webhookVerifyToken;
            $this->hasStoredWebhookVerifyToken = true;
        } elseif (! $setting->exists) {
            $setting->webhook_verify_token = null;
        }

        if (filled($this->appSecret)) {
            $setting->app_secret = $this->appSecret;
            $this->appSecret = '';
            $this->hasStoredAppSecret = true;
        }

        $setting->save();

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Configuracion de WhatsApp guardada correctamente.',
            duration: 4000
        );
    }

    public function syncMetaTemplates(WhatsAppTemplateSyncService $service): void
    {
        $result = $service->sync();
        $this->resetPage('metaTemplatesPage');

        $this->dispatch(
            'notify',
            type: $result['ok'] ? 'success' : 'error',
            content: $result['message'],
            duration: $result['ok'] ? 4000 : 7000
        );
    }

    public function createMetaTemplate(WhatsAppTemplateCreationService $service): void
    {
        $data = Validator::make([
            'newTemplateName' => $this->newTemplateName,
            'newTemplateLanguage' => $this->newTemplateLanguage,
            'newTemplateCategory' => $this->newTemplateCategory,
            'newTemplateHeaderType' => $this->newTemplateHeaderType,
            'newTemplateHeaderText' => $this->newTemplateHeaderText,
            'newTemplateHeaderExamples' => $this->newTemplateHeaderExamples,
            'newTemplateHeaderSample' => $this->newTemplateHeaderSample,
            'newTemplateBody' => $this->newTemplateBody,
            'newTemplateBodyExamples' => $this->newTemplateBodyExamples,
            'newTemplateFooter' => $this->newTemplateFooter,
        ], [
            'newTemplateName' => ['required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/'],
            'newTemplateLanguage' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})$/'],
            'newTemplateCategory' => ['required', Rule::in(['UTILITY', 'MARKETING'])],
            'newTemplateHeaderType' => ['required', Rule::in(['NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'])],
            'newTemplateHeaderText' => ['nullable', 'required_if:newTemplateHeaderType,TEXT', 'string', 'max:60'],
            'newTemplateHeaderExamples' => ['nullable', 'string', 'max:512'],
            'newTemplateHeaderSample' => ['nullable', 'required_if:newTemplateHeaderType,IMAGE,DOCUMENT', 'file', 'max:16384'],
            'newTemplateBody' => ['required', 'string', 'max:1024'],
            'newTemplateBodyExamples' => ['nullable', 'string', 'max:1024'],
            'newTemplateFooter' => ['nullable', 'string', 'max:60'],
        ], [
            'newTemplateName.regex' => 'Usa solo minusculas, numeros y guion bajo.',
            'newTemplateLanguage.regex' => 'Usa formato es_MX.',
        ])->validate();

        $this->validateTemplateVariableExamples($data);
        $this->validateHeaderSampleType();

        $result = $service->create([
            'name' => $data['newTemplateName'],
            'language' => $data['newTemplateLanguage'],
            'category' => $data['newTemplateCategory'],
            'header_type' => $data['newTemplateHeaderType'],
            'header_text' => $data['newTemplateHeaderText'] ?: null,
            'header_examples' => $data['newTemplateHeaderExamples'] ?: null,
            'header_sample' => $data['newTemplateHeaderSample'] ?? null,
            'body' => $data['newTemplateBody'],
            'body_examples' => $data['newTemplateBodyExamples'] ?: null,
            'footer' => $data['newTemplateFooter'] ?: null,
        ]);

        if ($result['ok']) {
            $this->resetPage('metaTemplatesPage');
            $this->reset([
                'newTemplateName',
                'newTemplateHeaderText',
                'newTemplateHeaderExamples',
                'newTemplateHeaderSample',
                'newTemplateBody',
                'newTemplateBodyExamples',
                'newTemplateFooter',
            ]);
            $this->newTemplateLanguage = 'es_MX';
            $this->newTemplateCategory = 'MARKETING';
            $this->newTemplateHeaderType = 'NONE';
            $this->dispatch('close-whatsapp-meta-template-modal');
        }

        $this->dispatch(
            'notify',
            type: $result['ok'] ? 'success' : 'error',
            content: $result['message'],
            duration: $result['ok'] ? 4000 : 8000
        );
    }

    public function toggleMetaTemplate(int $templateId): void
    {
        $template = WhatsAppMessageTemplate::query()->findOrFail($templateId);
        $template->update(['is_active' => ! $template->is_active]);
        $this->resetPage('metaTemplatesPage');

        $this->dispatch(
            'notify',
            type: 'success',
            content: $template->is_active ? 'Plantilla habilitada.' : 'Plantilla deshabilitada.',
            duration: 4000
        );
    }

    public function previewMetaTemplate(int $templateId): void
    {
        $this->previewTemplateId = $templateId;
        $this->dispatch('open-whatsapp-meta-template-preview-modal');
    }

    public function createOperationalTemplateFromMeta(int $templateId, WhatsAppTemplateDefinition $definition): void
    {
        $template = WhatsAppMessageTemplate::query()->findOrFail($templateId);

        if (! $template->isApproved()) {
            $this->dispatch('notify', type: 'error', content: 'Solo puedes operar plantillas aprobadas por Meta.', duration: 6000);
            return;
        }

        $requirements = $definition->requirements($template);
        $buttonVariables = $this->countButtonVariables($template);
        $bodyVariables = [];

        for ($index = 1; $index <= $requirements['body_variables']; $index++) {
            $bodyVariables[] = [
                'label' => 'Parametro body '.$index,
                'help_text' => '',
                'source_type' => 'custom',
                'system_key' => '',
                'example_value' => '',
                'required' => true,
            ];
        }

        $buttonMappings = [];
        for ($index = 1; $index <= $buttonVariables; $index++) {
            $buttonMappings[] = [
                'label' => 'Parametro boton '.$index,
                'help_text' => '',
                'source_type' => 'custom',
                'system_key' => '',
                'example_value' => '',
                'required' => true,
            ];
        }

        WhatsAppConsoleTemplate::query()->updateOrCreate(
            [
                'meta_template_name' => $template->name,
                'language_code' => $template->language_code,
            ],
            [
                'whatsapp_message_template_id' => $template->id,
                'name' => $template->name,
                'example_text' => $this->bodyText($template),
                'header_media_type' => $definition->headerMediaType($template),
                'body_variables' => $bodyVariables,
                'button_variables' => $buttonMappings,
                'is_active' => true,
                'allow_console' => true,
                'allow_marketing' => true,
            ]
        );

        $this->dispatch('notify', type: 'success', content: 'Plantilla operativa creada/actualizada para consola y campañas.', duration: 5000);
    }

    public function sendTestMessage(WhatsAppCloudApiService $service): void
    {
        Validator::make([
            'testPhone' => $this->testPhone,
            'testTemplateName' => $this->testTemplateName,
            'testLanguageCode' => $this->testLanguageCode,
            'testParameters' => $this->testParameters,
            'testButtonUrlParameters' => $this->testButtonUrlParameters,
        ], [
            'testPhone' => ['required', 'digits_between:8,20'],
            'testTemplateName' => ['required', 'string', 'max:255'],
            'testLanguageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'testParameters' => ['nullable', 'string'],
            'testButtonUrlParameters' => ['nullable', 'string'],
        ])->validate();

        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            $this->addError('testPhone', 'Primero debes guardar una configuracion valida de WhatsApp.');
            return;
        }

        $parameters = $this->extractParameters($this->testParameters);
        $buttonUrlParameters = $this->extractParameters($this->testButtonUrlParameters);

        $result = $service->sendTemplateMessage(
            setting: $setting,
            to: $this->testPhone,
            templateName: $this->testTemplateName,
            languageCode: $this->testLanguageCode,
            parameters: $parameters,
            buttonUrlParameters: $buttonUrlParameters
        );

        $this->lastTestMessageId = data_get($result['data'], 'messages.0.id');
        $this->lastTestResponse = json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($result['ok']) {
            $this->dispatch(
                'notify',
                type: 'success',
                content: 'Mensaje de prueba enviado correctamente.',
                duration: 4000
            );

            return;
        }

        $errorMessage = data_get($result['data'], 'error.message', 'No fue posible enviar el mensaje de prueba.');

        $this->dispatch(
            'notify',
            type: 'error',
            content: $errorMessage,
            duration: 6000
        );
    }

    public function addTemplateParameter(string $property): void
    {
        $scope = self::PARAMETER_SCOPE_MAP[$property] ?? null;

        if (! $scope) {
            return;
        }

        $options = array_keys($this->parameterOptions[$scope] ?? []);

        if ($options === []) {
            return;
        }

        $this->{$property}[] = $options[0];
    }

    public function removeTemplateParameter(string $property, int $index): void
    {
        if (! array_key_exists($property, self::PARAMETER_SCOPE_MAP)) {
            return;
        }

        $currentValues = $this->{$property};

        if (! array_key_exists($index, $currentValues)) {
            return;
        }

        unset($currentValues[$index]);
        $this->{$property} = array_values($currentValues);
    }

    /**
     * Convert each line from textarea into template body parameters.
     *
     * @return array<int, string>
     */
    private function extractParameters(string $raw): array
    {
        return collect(preg_split('/\R/', $raw) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function hydrateDefaultParameterMappings(WhatsAppTemplateParameterResolver $resolver): void
    {
        $this->systemUserActivationBodyParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BODY)
        );
        $this->systemUserActivationButtonParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BUTTON)
        );
        $this->activationBodyParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::ACTIVATION_BODY)
        );
        $this->activationButtonParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::ACTIVATION_BUTTON)
        );
        $this->pinResetBodyParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PIN_RESET_BODY)
        );
        $this->pinResetButtonParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PIN_RESET_BUTTON)
        );
        $this->preregistrationBodyParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PREREGISTRATION_BODY)
        );
        $this->preregistrationButtonParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::PREREGISTRATION_BUTTON)
        );
        $this->appointmentRequestBodyParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BODY)
        );
        $this->appointmentRequestButtonParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BUTTON)
        );
        $this->appointmentCompletedBodyParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BODY)
        );
        $this->appointmentCompletedButtonParameters = $this->normalizeConfiguredParameters(
            $resolver->defaultKeys(WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BUTTON)
        );
    }

    /**
     * @param  array<int, string>|string|null  $parameters
     * @return array<int, string>
     */
    private function normalizeConfiguredParameters(array|string|null $parameters): array
    {
        return array_values(array_filter(
            app(WhatsAppTemplateParameterResolver::class)->extractKeys($parameters),
            fn (string $parameter) => $parameter !== ''
        ));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validatedTemplateMappings(WhatsAppTemplateParameterResolver $resolver): array
    {
        $mappings = [
            'system_user_activation_body_parameters' => $resolver->extractKeys($this->systemUserActivationBodyParameters),
            'system_user_activation_button_parameters' => $resolver->extractKeys($this->systemUserActivationButtonParameters),
            'activation_body_parameters' => $resolver->extractKeys($this->activationBodyParameters),
            'activation_button_parameters' => $resolver->extractKeys($this->activationButtonParameters),
            'pin_reset_body_parameters' => $resolver->extractKeys($this->pinResetBodyParameters),
            'pin_reset_button_parameters' => $resolver->extractKeys($this->pinResetButtonParameters),
            'preregistration_body_parameters' => $resolver->extractKeys($this->preregistrationBodyParameters),
            'preregistration_button_parameters' => $resolver->extractKeys($this->preregistrationButtonParameters),
            'appointment_request_body_parameters' => $resolver->extractKeys($this->appointmentRequestBodyParameters),
            'appointment_request_button_parameters' => $resolver->extractKeys($this->appointmentRequestButtonParameters),
            'appointment_completed_body_parameters' => $resolver->extractKeys($this->appointmentCompletedBodyParameters),
            'appointment_completed_button_parameters' => $resolver->extractKeys($this->appointmentCompletedButtonParameters),
        ];

        $scopes = [
            'system_user_activation_body_parameters' => WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BODY,
            'system_user_activation_button_parameters' => WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BUTTON,
            'activation_body_parameters' => WhatsAppTemplateParameterResolver::ACTIVATION_BODY,
            'activation_button_parameters' => WhatsAppTemplateParameterResolver::ACTIVATION_BUTTON,
            'pin_reset_body_parameters' => WhatsAppTemplateParameterResolver::PIN_RESET_BODY,
            'pin_reset_button_parameters' => WhatsAppTemplateParameterResolver::PIN_RESET_BUTTON,
            'preregistration_body_parameters' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BODY,
            'preregistration_button_parameters' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BUTTON,
            'appointment_request_body_parameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BODY,
            'appointment_request_button_parameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BUTTON,
            'appointment_completed_body_parameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BODY,
            'appointment_completed_button_parameters' => WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BUTTON,
        ];

        $errors = [];

        foreach ($scopes as $field => $scope) {
            $invalidKeys = $resolver->invalidKeys($mappings[$field], $scope);

            if ($invalidKeys !== []) {
                $property = match ($field) {
                    'system_user_activation_body_parameters' => 'systemUserActivationBodyParameters',
                    'system_user_activation_button_parameters' => 'systemUserActivationButtonParameters',
                    'activation_body_parameters' => 'activationBodyParameters',
                    'activation_button_parameters' => 'activationButtonParameters',
                    'pin_reset_body_parameters' => 'pinResetBodyParameters',
                    'pin_reset_button_parameters' => 'pinResetButtonParameters',
                    'preregistration_body_parameters' => 'preregistrationBodyParameters',
                    'preregistration_button_parameters' => 'preregistrationButtonParameters',
                    'appointment_request_body_parameters' => 'appointmentRequestBodyParameters',
                    'appointment_request_button_parameters' => 'appointmentRequestButtonParameters',
                    'appointment_completed_body_parameters' => 'appointmentCompletedBodyParameters',
                    default => 'appointmentCompletedButtonParameters',
                };

                $errors[$property] = 'Llaves invalidas: '.implode(', ', $invalidKeys);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $mappings;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateTemplateVariableExamples(array $data): void
    {
        foreach ([
            ['newTemplateBody', 'newTemplateBodyExamples', $data['newTemplateBody'] ?? ''],
            ['newTemplateHeaderText', 'newTemplateHeaderExamples', $data['newTemplateHeaderText'] ?? ''],
        ] as [$textField, $examplesField, $text]) {
            if ($textField === 'newTemplateHeaderText' && ($data['newTemplateHeaderType'] ?? 'NONE') !== 'TEXT') {
                continue;
            }

            $variables = $this->templateVariables((string) $text);

            if (! $this->hasValidVariableSyntax((string) $text)) {
                throw ValidationException::withMessages([
                    $textField => 'Cada variable debe escribirse como {{1}}, {{2}}, sin espacios ni ceros.',
                ]);
            }

            if (! $this->hasSequentialVariables($variables)) {
                throw ValidationException::withMessages([
                    $textField => 'Las variables deben ser consecutivas, por ejemplo {{1}}, {{2}}.',
                ]);
            }

            if ($variables !== [] && count($this->templateExamples($data[$examplesField] ?? null)) !== count($variables)) {
                throw ValidationException::withMessages([
                    $examplesField => 'Incluye un ejemplo por cada variable, separados con |.',
                ]);
            }
        }
    }

    private function validateHeaderSampleType(): void
    {
        if ($this->newTemplateHeaderType === 'IMAGE' && $this->newTemplateHeaderSample) {
            $mime = (string) $this->newTemplateHeaderSample->getMimeType();

            if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
                throw ValidationException::withMessages([
                    'newTemplateHeaderSample' => 'Para imagen, carga un archivo JPEG o PNG.',
                ]);
            }
        }

        if ($this->newTemplateHeaderType === 'DOCUMENT' && $this->newTemplateHeaderSample) {
            if ($this->newTemplateHeaderSample->getMimeType() !== 'application/pdf') {
                throw ValidationException::withMessages([
                    'newTemplateHeaderSample' => 'Para documento, carga un archivo PDF.',
                ]);
            }
        }

        if ($this->newTemplateHeaderType === 'VIDEO' && $this->newTemplateHeaderSample) {
            $mime = (string) $this->newTemplateHeaderSample->getMimeType();

            if (! in_array($mime, ['video/mp4', 'video/3gpp', 'video/quicktime'], true)) {
                throw ValidationException::withMessages([
                    'newTemplateHeaderSample' => 'Para video, carga un archivo MP4, 3GPP o MOV.',
                ]);
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function templateVariables(string $text): array
    {
        preg_match_all('/{{\s*(\d+)\s*}}/', $text, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $value): int => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function hasValidVariableSyntax(string $text): bool
    {
        return ! preg_match('/{{\s*0|\{\{\s*\D|{{[^}]*\s+[^}]*}}/', $text);
    }

    /**
     * @param  array<int, int>  $variables
     */
    private function hasSequentialVariables(array $variables): bool
    {
        if ($variables === []) {
            return true;
        }

        return $variables === range(1, count($variables));
    }

    /**
     * @return array<int, string>
     */
    private function templateExamples(mixed $value): array
    {
        return collect(explode('|', (string) $value))
            ->map(fn (string $example): string => trim($example))
            ->filter()
            ->values()
            ->all();
    }

    private function countButtonVariables(WhatsAppMessageTemplate $template): int
    {
        $count = 0;

        foreach ($template->components ?? [] as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) !== 'BUTTONS') {
                continue;
            }

            foreach ((array) ($component['buttons'] ?? []) as $button) {
                if (strtoupper((string) ($button['type'] ?? '')) !== 'URL') {
                    continue;
                }

                preg_match_all('/{{\s*(\d+)\s*}}/', (string) ($button['url'] ?? ''), $matches);
                $count += count(array_unique($matches[1] ?? []));
            }
        }

        return $count;
    }

    private function bodyText(WhatsAppMessageTemplate $template): string
    {
        foreach ($template->components ?? [] as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) === 'BODY') {
                return (string) ($component['text'] ?? '');
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function metaTemplatePreviewData(): array
    {
        $template = $this->previewTemplateId
            ? WhatsAppMessageTemplate::query()->find($this->previewTemplateId)
            : null;

        if (! $template) {
            return [
                'template' => null,
                'header' => null,
                'body' => null,
                'footer' => null,
                'buttons' => [],
            ];
        }

        $components = collect($template->components ?? []);
        $header = $components->first(fn (array $component): bool => strtoupper((string) ($component['type'] ?? '')) === 'HEADER');
        $body = $components->first(fn (array $component): bool => strtoupper((string) ($component['type'] ?? '')) === 'BODY');
        $footer = $components->first(fn (array $component): bool => strtoupper((string) ($component['type'] ?? '')) === 'FOOTER');
        $buttons = $components->first(fn (array $component): bool => strtoupper((string) ($component['type'] ?? '')) === 'BUTTONS');

        return [
            'template' => $template,
            'header' => is_array($header) ? $header : null,
            'body' => is_array($body) ? $body : null,
            'footer' => is_array($footer) ? $footer : null,
            'buttons' => is_array($buttons) ? (array) ($buttons['buttons'] ?? []) : [],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildTemplateSections(): array
    {
        return [
            [
                'title' => 'Activacion PIN usuario sistema',
                'template_field' => 'systemUserActivationTemplateName',
                'language_field' => 'systemUserActivationLanguageCode',
                'body_label' => 'Body',
                'body_field' => 'systemUserActivationBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'systemUserActivationButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::SYSTEM_USER_ACTIVATION_BUTTON,
            ],
            [
                'title' => 'Activacion PIN',
                'template_field' => 'activationTemplateName',
                'language_field' => 'activationLanguageCode',
                'body_label' => 'Body',
                'body_field' => 'activationBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::ACTIVATION_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'activationButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::ACTIVATION_BUTTON,
            ],
            [
                'title' => 'Reset PIN',
                'template_field' => 'pinResetTemplateName',
                'language_field' => 'pinResetLanguageCode',
                'body_label' => 'Body',
                'body_field' => 'pinResetBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::PIN_RESET_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'pinResetButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::PIN_RESET_BUTTON,
            ],
            [
                'title' => 'Preregistro',
                'template_field' => 'preregistrationTemplateName',
                'language_field' => 'preregistrationLanguageCode',
                'body_label' => 'Body',
                'body_field' => 'preregistrationBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'preregistrationButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BUTTON,
            ],
            [
                'title' => 'Solicitud de cita',
                'template_field' => 'appointmentRequestTemplateName',
                'language_field' => 'appointmentRequestLanguageCode',
                'body_label' => 'Body',
                'body_field' => 'appointmentRequestBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'appointmentRequestButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BUTTON,
            ],
            [
                'title' => 'Cita finalizada',
                'template_field' => 'appointmentCompletedTemplateName',
                'language_field' => 'appointmentCompletedLanguageCode',
                'body_label' => 'Body',
                'body_field' => 'appointmentCompletedBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'appointmentCompletedButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::APPOINTMENT_COMPLETED_BUTTON,
            ],
        ];
    }
}
