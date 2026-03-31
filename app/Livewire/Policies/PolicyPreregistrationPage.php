<?php

namespace App\Livewire\Policies;

use App\Livewire\Forms\GroupPolicyForm;
use App\Livewire\Forms\IndividualPolicyForm;
use App\Models\Plan;
use App\Models\PolicyPreregistration;
use App\Services\Auth\PolicyPreregistrationService;
use App\Services\Policies\GroupPolicyRegistrationService;
use App\Services\Policies\IndividualPolicyRegistrationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class PolicyPreregistrationPage extends Component
{
    use WithFileUploads;

    public IndividualPolicyForm $form;

    public GroupPolicyForm $groupForm;

    public string $token = '';

    public ?PolicyPreregistration $preregistration = null;

    public string $tokenStatus = PolicyPreregistrationService::STATUS_INVALID;

    public ?string $tokenMessage = null;

    public bool $registrationCompleted = false;

    public string $registeredMemberName = '';

    public string $officeMapsUrl = 'https://maps.app.goo.gl/5TQTNEzeJ3FKCK6Z9';

    public Collection $groupPlans;

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
        $this->groupPlans = Plan::query()
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->syncFormWithPreregistration($this->preregistration);
    }

    public function save(
        PolicyPreregistrationService $preregistrationService,
        IndividualPolicyRegistrationService $registrationService,
        GroupPolicyRegistrationService $groupRegistrationService
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

        $policy = $this->preregistration->isGroupOwner()
            ? $this->groupForm->store($groupRegistrationService, $this->preregistration->id)
            : $this->form->store($registrationService, $this->preregistration->id);
        $preregistrationService->consumeToken($this->preregistration);
        $this->registrationCompleted = true;
        $this->registeredMemberName = $policy->user->name;
        $this->tokenStatus = PolicyPreregistrationService::STATUS_USED;
        $this->tokenMessage = null;

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Tus datos fueron registrados correctamente. Tu membresia esta pendiente de activacion.',
            duration: 5000
        );
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

    #[Computed]
    public function groupAge(): ?int
    {
        if (! $this->groupForm->birth) {
            return null;
        }

        try {
            return Carbon::parse($this->groupForm->birth)->age;
        } catch (\Exception) {
            return null;
        }
    }

    private function syncFormWithPreregistration(?PolicyPreregistration $preregistration): void
    {
        if (! $preregistration) {
            return;
        }

        if ($preregistration->isGroupOwner()) {
            $this->groupForm->company = (string) $preregistration->company_name;
            $this->groupForm->type = $preregistration->company_type ?: 'PF';
            $this->groupForm->legal_name = (string) $preregistration->company_legal_name;
            $this->groupForm->rfc = (string) $preregistration->company_rfc;
            $this->groupForm->phone = $preregistration->phone;
            $this->groupForm->plan = $preregistration->plan_id ? (string) $preregistration->plan_id : null;
            $this->groupForm->sales_user = (string) $preregistration->sales_user_id;

            return;
        }

        $this->form->addingMember = $preregistration->isGroupMember();
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
