<?php

namespace App\Livewire\Policies;

use App\Livewire\Forms\IndividualPolicyForm;
use App\Models\PolicyPreregistration;
use App\Services\Auth\PinSetupTokenService;
use App\Services\Auth\PolicyPreregistrationService;
use App\Services\Policies\IndividualPolicyRegistrationService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class PolicyPreregistrationPage extends Component
{
    use WithFileUploads;

    public IndividualPolicyForm $form;

    public string $token = '';

    public ?PolicyPreregistration $preregistration = null;

    public string $tokenStatus = PolicyPreregistrationService::STATUS_INVALID;

    public ?string $tokenMessage = null;

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.policies.policy-preregistration-page');
    }

    public function mount(string $token, PolicyPreregistrationService $service): void
    {
        $this->token = $token;

        $resolved = $service->resolveTokenStatus($this->token);
        $this->tokenStatus = $resolved['status'];
        $this->preregistration = $resolved['preregistration'];
        $this->tokenMessage = $this->resolveTokenMessage($this->tokenStatus);

        $this->syncFormWithPreregistration($this->preregistration);
    }

    public function save(
        PolicyPreregistrationService $preregistrationService,
        IndividualPolicyRegistrationService $registrationService,
        PinSetupTokenService $pinSetupTokenService
    ) {
        $resolved = $preregistrationService->resolveTokenStatus($this->token);
        $this->tokenStatus = $resolved['status'];
        $this->preregistration = $resolved['preregistration'];
        $this->tokenMessage = $this->resolveTokenMessage($this->tokenStatus);

        if (! $this->canRegister() || ! $this->preregistration) {
            throw ValidationException::withMessages([
                'form.phone' => $this->tokenMessage ?: 'Esta invitacion ya no esta disponible.',
            ]);
        }

        $this->syncFormWithPreregistration($this->preregistration);

        $policy = $this->form->store($registrationService, $this->preregistration->id);
        $pinSetup = $pinSetupTokenService->generateSetupLink(
            $policy->user,
            null,
            PinSetupTokenService::PURPOSE_ACTIVATION,
            false
        );

        $preregistrationService->consumeToken($this->preregistration);

        return redirect()->to($pinSetup['url']);
    }

    public function canRegister(): bool
    {
        return $this->tokenStatus === PolicyPreregistrationService::STATUS_ACTIVE;
    }

    #[Computed]
    public function age(): ?int
    {
        if (! $this->form->birth) {
            return null;
        }

        try {
            return Carbon::parse($this->form->birth)->age;
        } catch (\Exception) {
            return null;
        }
    }

    private function syncFormWithPreregistration(?PolicyPreregistration $preregistration): void
    {
        if (! $preregistration) {
            return;
        }

        $this->form->phone = $preregistration->phone;
        $this->form->plan = (string) $preregistration->plan_id;
        $this->form->parent_policy = $preregistration->parent_policy_id
            ? (string) $preregistration->parent_policy_id
            : null;
        $this->form->sales_user = (string) $preregistration->sales_user_id;
    }

    private function resolveTokenMessage(string $status): string
    {
        return match ($status) {
            PolicyPreregistrationService::STATUS_CANCELLED => 'Esta invitacion fue cancelada. Solicita una nueva al promotor.',
            PolicyPreregistrationService::STATUS_USED => 'Esta invitacion ya fue utilizada. Solicita una nueva al promotor.',
            PolicyPreregistrationService::STATUS_EXPIRED => 'Esta invitacion ya vencio. Solicita una nueva al promotor.',
            PolicyPreregistrationService::STATUS_ACTIVE => '',
            default => 'Esta invitacion es invalida. Solicita un enlace nuevo al promotor.',
        };
    }
}
