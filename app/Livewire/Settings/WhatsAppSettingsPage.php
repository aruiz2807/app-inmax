<?php

namespace App\Livewire\Settings;

use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

class WhatsAppSettingsPage extends Component
{
    public string $apiVersion = 'v22.0';
    public string $phoneNumberId = '';
    public string $accessToken = '';
    public string $activationTemplateName = '';
    public string $pinResetTemplateName = '';
    public string $defaultLanguage = 'es_MX';
    public bool $hasStoredAccessToken = false;

    public string $testPhone = '';
    public string $testTemplateName = '';
    public string $testLanguageCode = 'es_MX';
    public string $testParameters = '';
    public ?string $lastTestMessageId = null;
    public ?string $lastTestResponse = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.settings.whatsapp-settings-page');
    }

    public function mount(): void
    {
        $setting = WhatsAppSetting::query()->first();

        if (! $setting) {
            return;
        }

        $this->apiVersion = $setting->api_version;
        $this->phoneNumberId = $setting->phone_number_id ?? '';
        $this->activationTemplateName = $setting->activation_template_name ?? '';
        $this->pinResetTemplateName = $setting->pin_reset_template_name ?? '';
        $this->defaultLanguage = $setting->default_language;
        $this->testLanguageCode = $setting->default_language;
        $this->hasStoredAccessToken = filled($setting->access_token);
    }

    public function saveSettings(): void
    {
        $rules = [
            'apiVersion' => ['required', 'regex:/^v\d+\.\d+$/'],
            'phoneNumberId' => ['required', 'digits_between:8,30'],
            'activationTemplateName' => ['required', 'string', 'max:255'],
            'pinResetTemplateName' => ['required', 'string', 'max:255'],
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
            'defaultLanguage' => $this->defaultLanguage,
        ], $rules, [
            'apiVersion.regex' => 'El formato de version debe ser vNN.N (ej. v22.0).',
            'defaultLanguage.regex' => 'El idioma debe tener formato es o es_MX.',
        ])->validate();

        $setting = WhatsAppSetting::query()->firstOrNew(['id' => 1]);

        $setting->api_version = $this->apiVersion;
        $setting->phone_number_id = $this->phoneNumberId;
        $setting->activation_template_name = $this->activationTemplateName;
        $setting->pin_reset_template_name = $this->pinResetTemplateName;
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
        ], [
            'testPhone' => ['required', 'digits_between:8,20'],
            'testTemplateName' => ['required', 'string', 'max:255'],
            'testLanguageCode' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'testParameters' => ['nullable', 'string'],
        ])->validate();

        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            $this->addError('testPhone', 'Primero debes guardar una configuracion valida de WhatsApp.');
            return;
        }

        $parameters = $this->extractParameters($this->testParameters);

        $result = $service->sendTemplateMessage(
            setting: $setting,
            to: $this->testPhone,
            templateName: $this->testTemplateName,
            languageCode: $this->testLanguageCode,
            parameters: $parameters
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
}
