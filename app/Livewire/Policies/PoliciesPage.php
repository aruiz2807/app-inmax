<?php

namespace App\Livewire\Policies;

use App\Models\Policy;
use App\Models\Plan;
use App\Services\Auth\PolicyPreregistrationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use InvalidArgumentException;

class PoliciesPage extends Component
{
    public ?int $policyId = null;
    public ?string $policyType = null;
    public $newMember = false;

    public $policy_number = '';
    public $policy_user_name = '';
    public string $preregistrationPhone = '';
    public ?string $preregistrationPlan = null;
    public ?string $preregistrationParentPolicy = null;
    public ?string $lastPreregistrationUrl = null;
    public ?string $lastPreregistrationPhone = null;
    public ?string $lastPreregistrationPlanName = null;
    public ?string $lastPreregistrationExpiresAt = null;
    public Collection $preregistrationPlans;
    public Collection $preregistrationParentPolicies;

    public function mount(): void
    {
        $this->loadPreregistrationOptions();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.policies-page');
    }

    public function savePreregistration(PolicyPreregistrationService $service): void
    {
        $validated = $this->validate([
            'preregistrationPhone' => ['required', 'digits:10', 'unique:users,phone'],
            'preregistrationPlan' => ['required'],
            'preregistrationParentPolicy' => ['nullable'],
        ], [
            'preregistrationPhone.unique' => 'Ya existe un usuario registrado con ese telefono.',
        ]);

        try {
            $result = $service->createInvitation(
                Auth::user(),
                $validated['preregistrationPhone'],
                (int) $validated['preregistrationPlan'],
                filled($validated['preregistrationParentPolicy'])
                    ? (int) $validated['preregistrationParentPolicy']
                    : null
            );
        } catch (InvalidArgumentException $exception) {
            $field = match (true) {
                str_contains($exception->getMessage(), 'telefono') => 'preregistrationPhone',
                str_contains($exception->getMessage(), 'principal') => 'preregistrationParentPolicy',
                default => 'preregistrationPlan',
            };

            throw ValidationException::withMessages([
                $field => $exception->getMessage(),
            ]);
        }

        $plan = $this->preregistrationPlans
            ->firstWhere('id', (int) $validated['preregistrationPlan']);

        $this->lastPreregistrationUrl = $result['url'];
        $this->lastPreregistrationPhone = $validated['preregistrationPhone'];
        $this->lastPreregistrationPlanName = $plan?->name;
        $this->lastPreregistrationExpiresAt = $result['expires_at']->format('d/m/Y H:i');

        $content = match (true) {
            ($result['whatsapp']['ok'] ?? false) => 'Preregistro creado y enlace enviado por WhatsApp.',
            ($result['whatsapp']['attempted'] ?? false) => 'Preregistro creado. No se pudo enviar WhatsApp, enlace disponible para prueba.',
            default => 'Preregistro creado. Falta configurar WhatsApp, enlace disponible para prueba.',
        };

        $this->dispatch(
            'notify',
            type: 'success',
            content: $content,
            duration: 4000
        );

        $this->dispatch('close-preregistration-modal');
        $this->resetPreregistrationForm();
    }

    #[On('editPolicy')]
    public function edit($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyType = $policy->plan->type;
        $this->policyId = $policyId;

        //open modal
        $this->dispatch('open-policy-modal');
    }

    #[On('addMember')]
    public function addMember($policyId)
    {
        $this->policyType = 'Individual';
        $this->policyId = $policyId;
        $this->newMember = true;

        //open modal
        $this->dispatch('open-policy-modal');
    }

    #[On('activatePolicy')]
    public function activate($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        //open new modal
        $this->dispatch('open-activation-modal');

    }

    #[On('deactivatePolicy')]
    public function deactivate($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        //open modal
        $this->dispatch('open-deactivation-modal');

    }

    #[On('cancelPolicy')]
    public function cancel($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        //open modal
        $this->dispatch('open-cancel-modal');

    }

    public function confirmActivation()
    {
        $policy = Policy::findOrFail($this->policyId);

        $start = Carbon::now()->addDays(5);
        $end = Carbon::now()->addDays(5)->addYear();

        $policy->update([
            'status' => 'Active',
            'start_date' => $start,
            'end_date' => $end,
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza activada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-activation-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmDeactivation()
    {
        $policy = Policy::findOrFail($this->policyId);

        $policy->update([
            'status' => 'Inactive',
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza desactivada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-deactivation-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmCancel()
    {
        $policy = Policy::findOrFail($this->policyId);
        $now = Carbon::now();

        $policy->update([
            'status' => 'Cancelled',
            'end_date' => $now,
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza cancelada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-cancel-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function selectType($type)
    {
        $this->policyType = $type;
    }

    public function resetPreregistrationForm(): void
    {
        $this->resetValidation([
            'preregistrationPhone',
            'preregistrationPlan',
            'preregistrationParentPolicy',
        ]);

        $this->preregistrationPhone = '';
        $this->preregistrationPlan = null;
        $this->preregistrationParentPolicy = null;
    }

    public function resetForm()
    {
        $this->policyId = null;
        $this->policyType = null;
        $this->newMember = false;
    }

    private function loadPreregistrationOptions(): void
    {
        $this->preregistrationPlans = Plan::query()
            ->where('type', 'Individual')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->preregistrationParentPolicies = Policy::query()
            ->with(['user:id,name,company_id', 'user.company:id,name'])
            ->whereNull('parent_policy_id')
            ->whereHas('plan', function ($query) {
                $query->where('type', 'Individual');
            })
            ->orderBy('number')
            ->get();
    }
}
