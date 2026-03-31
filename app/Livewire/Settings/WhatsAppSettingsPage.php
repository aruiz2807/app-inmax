<?php

namespace App\Livewire\Settings;

use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Services\WhatsApp\WhatsAppTemplateParameterResolver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class WhatsAppSettingsPage extends Component
{
    private const PARAMETER_SCOPE_MAP = [
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
    public string $accessToken = '';
    public string $activationTemplateName = '';
    public string $pinResetTemplateName = '';
    public string $preregistrationTemplateName = '';
    public string $appointmentRequestTemplateName = '';
    public string $appointmentCompletedTemplateName = '';
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
    public array $parameterOptions = [];
    public array $templateSections = [];

    public string $testPhone = '';
    public string $testTemplateName = '';
    public string $testLanguageCode = 'es_MX';
    public string $testParameters = '';
    public string $testButtonUrlParameters = '';
    public ?string $lastTestMessageId = null;
    public ?string $lastTestResponse = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.settings.whatsapp-settings-page');
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
        $this->activationTemplateName = $setting->activation_template_name ?? '';
        $this->pinResetTemplateName = $setting->pin_reset_template_name ?? '';
        $this->preregistrationTemplateName = $setting->preregistration_template_name ?? '';
        $this->appointmentRequestTemplateName = $setting->appointment_request_template_name ?? '';
        $this->appointmentCompletedTemplateName = $setting->appointment_completed_template_name ?? '';
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
    }

    public function saveSettings(): void
    {
        $resolver = app(WhatsAppTemplateParameterResolver::class);

        $rules = [
            'apiVersion' => ['required', 'regex:/^v\d+\.\d+$/'],
            'phoneNumberId' => ['required', 'digits_between:8,30'],
            'activationTemplateName' => ['required', 'string', 'max:255'],
            'pinResetTemplateName' => ['required', 'string', 'max:255'],
            'preregistrationTemplateName' => ['required', 'string', 'max:255'],
            'appointmentRequestTemplateName' => ['required', 'string', 'max:255'],
            'appointmentCompletedTemplateName' => ['required', 'string', 'max:255'],
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

        Validator::make([
            'apiVersion' => $this->apiVersion,
            'phoneNumberId' => $this->phoneNumberId,
            'accessToken' => $this->accessToken,
            'activationTemplateName' => $this->activationTemplateName,
            'pinResetTemplateName' => $this->pinResetTemplateName,
            'preregistrationTemplateName' => $this->preregistrationTemplateName,
            'appointmentRequestTemplateName' => $this->appointmentRequestTemplateName,
            'appointmentCompletedTemplateName' => $this->appointmentCompletedTemplateName,
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
        $setting->activation_template_name = $this->activationTemplateName;
        $setting->activation_body_parameters = $mappings['activation_body_parameters'];
        $setting->activation_button_parameters = $mappings['activation_button_parameters'];
        $setting->pin_reset_template_name = $this->pinResetTemplateName;
        $setting->pin_reset_body_parameters = $mappings['pin_reset_body_parameters'];
        $setting->pin_reset_button_parameters = $mappings['pin_reset_button_parameters'];
        $setting->preregistration_template_name = $this->preregistrationTemplateName;
        $setting->preregistration_body_parameters = $mappings['preregistration_body_parameters'];
        $setting->preregistration_button_parameters = $mappings['preregistration_button_parameters'];
        $setting->appointment_request_template_name = $this->appointmentRequestTemplateName;
        $setting->appointment_request_body_parameters = $mappings['appointment_request_body_parameters'];
        $setting->appointment_request_button_parameters = $mappings['appointment_request_button_parameters'];
        $setting->appointment_completed_template_name = $this->appointmentCompletedTemplateName;
        $setting->appointment_completed_body_parameters = $mappings['appointment_completed_body_parameters'];
        $setting->appointment_completed_button_parameters = $mappings['appointment_completed_button_parameters'];
        $setting->default_language = $this->defaultLanguage;

        if (filled($this->accessToken)) {
            $setting->access_token = $this->accessToken;
            $this->accessToken = '';
            $this->hasStoredAccessToken = true;
        }

        $setting->save();

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Configuracion de WhatsApp guardada correctamente.',
            duration: 4000
        );
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
     * @return array<int, array<string, string>>
     */
    private function buildTemplateSections(): array
    {
        return [
            [
                'title' => 'Activacion PIN',
                'body_label' => 'Body',
                'body_field' => 'activationBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::ACTIVATION_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'activationButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::ACTIVATION_BUTTON,
            ],
            [
                'title' => 'Reset PIN',
                'body_label' => 'Body',
                'body_field' => 'pinResetBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::PIN_RESET_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'pinResetButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::PIN_RESET_BUTTON,
            ],
            [
                'title' => 'Preregistro',
                'body_label' => 'Body',
                'body_field' => 'preregistrationBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'preregistrationButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::PREREGISTRATION_BUTTON,
            ],
            [
                'title' => 'Solicitud de cita',
                'body_label' => 'Body',
                'body_field' => 'appointmentRequestBodyParameters',
                'body_scope' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BODY,
                'button_label' => 'Boton URL',
                'button_field' => 'appointmentRequestButtonParameters',
                'button_scope' => WhatsAppTemplateParameterResolver::APPOINTMENT_REQUEST_BUTTON,
            ],
            [
                'title' => 'Cita finalizada',
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
