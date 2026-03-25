<?php

namespace App\Livewire\Policies;

use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyPreregistration;
use App\Models\User;
use App\Services\Auth\PolicyPreregistrationService;
use App\Services\Policies\GroupPolicyCapacityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class PolicyPreregistrationsPage extends Component
{
    use WithPagination;

    public string $preregistrationType = PolicyPreregistration::TYPE_INDIVIDUAL_POLICY;

    public string $preregistrationPhone = '';

    public ?string $preregistrationPlan = null;

    public ?string $preregistrationParentPolicy = null;

    public ?string $preregistrationSalesUser = null;

    public ?string $lastPreregistrationUrl = null;

    public ?string $lastPreregistrationPhone = null;

    public ?string $lastPreregistrationPlanName = null;

    public ?string $lastPreregistrationExpiresAt = null;

    public ?int $preregistrationId = null;

    public ?int $preregistrationToCancelId = null;

    public string $preregistrationToCancelPhone = '';

    public string $filterPreregistrationPhone = '';

    public string $filterPreregistrationPlan = '';

    public string $filterPreregistrationType = '';

    public string $filterPreregistrationStatus = '';

    public int $preregistrationPerPage = 10;

    public Collection $preregistrationPlans;

    public Collection $preregistrationFilterPlans;

    public Collection $preregistrationParentPolicies;

    public Collection $preregistrationGroupPolicies;

    public Collection $preregistrationSalesAgents;

    public bool $shouldOpenPrefilledPreregistrationModal = false;

    public function mount(): void
    {
        $this->loadPreregistrationOptions();
        $this->applyRequestPrefill();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.policy-preregistrations-page', [
            'preregistrations' => $this->preregistrationsQuery()->paginate($this->preregistrationPerPage),
        ]);
    }

    public function savePreregistration(PolicyPreregistrationService $service): void
    {
        $isEditing = $this->preregistrationId !== null;

        $validated = $this->validate([
            'preregistrationType' => ['required', Rule::in([
                PolicyPreregistration::TYPE_INDIVIDUAL_POLICY,
                PolicyPreregistration::TYPE_GROUP_MEMBER,
            ])],
            'preregistrationPhone' => [
                'required',
                'digits:10',
                Rule::unique('users', 'phone'),
                Rule::unique('policy_preregistrations', 'phone')->ignore($this->preregistrationId),
            ],
            'preregistrationPlan' => [
                Rule::requiredIf($this->preregistrationType === PolicyPreregistration::TYPE_INDIVIDUAL_POLICY),
                'nullable',
            ],
            'preregistrationParentPolicy' => [
                Rule::requiredIf($this->preregistrationType === PolicyPreregistration::TYPE_GROUP_MEMBER),
                'nullable',
            ],
            'preregistrationSalesUser' => ['required', 'exists:users,id'],
        ], [
            'preregistrationPhone.unique' => 'Ya existe un registro con ese telefono.',
            'preregistrationPhone.required' => 'El telefono es obligatorio.',
            'preregistrationPhone.digits' => 'El telefono debe contener 10 digitos.',
            'preregistrationParentPolicy.required' => 'Selecciona la poliza colectiva.',
            'preregistrationPlan.required' => 'Selecciona la cobertura.',
        ]);

        $salesUser = $this->resolveSalesUser((int) $validated['preregistrationSalesUser']);
        $planId = $this->resolvePreregistrationPlanId($validated);
        $parentPolicyId = filled($validated['preregistrationParentPolicy'])
            ? (int) $validated['preregistrationParentPolicy']
            : null;

        try {
            if (! $isEditing) {
                $result = $service->createInvitation(
                    $salesUser,
                    $validated['preregistrationPhone'],
                    $planId,
                    $parentPolicyId,
                    $validated['preregistrationType'],
                );
            } else {
                $preregistration = $this->resolveManagedPreregistration($this->preregistrationId);
                $result = $service->updateInvitation(
                    $preregistration,
                    $salesUser,
                    $validated['preregistrationPhone'],
                    $planId,
                    $parentPolicyId,
                    $validated['preregistrationType'],
                );
            }
        } catch (InvalidArgumentException $exception) {
            $field = match (true) {
                str_contains($exception->getMessage(), 'telefono') => 'preregistrationPhone',
                str_contains($exception->getMessage(), 'lugares disponibles') => 'preregistrationParentPolicy',
                str_contains($exception->getMessage(), 'colectiva') => 'preregistrationParentPolicy',
                str_contains($exception->getMessage(), 'principal') => 'preregistrationParentPolicy',
                str_contains($exception->getMessage(), 'cobertura') => 'preregistrationPlan',
                default => 'preregistrationPhone',
            };

            throw ValidationException::withMessages([
                $field => $exception->getMessage(),
            ]);
        }

        $this->lastPreregistrationUrl = $result['url'];
        $this->lastPreregistrationPhone = $validated['preregistrationPhone'];
        $this->lastPreregistrationPlanName = $result['preregistration']->plan?->name;
        $this->lastPreregistrationExpiresAt = $result['expires_at']->format('d/m/Y H:i');

        $content = match (true) {
            ($result['whatsapp']['ok'] ?? false) => $isEditing
                ? 'Preregistro actualizado y enlace reenviado por WhatsApp.'
                : 'Preregistro creado y enlace enviado por WhatsApp.',
            ($result['whatsapp']['attempted'] ?? false) => $isEditing
                ? 'Preregistro actualizado. No se pudo enviar WhatsApp, enlace disponible para prueba.'
                : 'Preregistro creado. No se pudo enviar WhatsApp, enlace disponible para prueba.',
            default => $isEditing
                ? 'Preregistro actualizado. Falta configurar WhatsApp, enlace disponible para prueba.'
                : 'Preregistro creado. Falta configurar WhatsApp, enlace disponible para prueba.',
        };

        $this->dispatch(
            'notify',
            type: 'success',
            content: $content,
            duration: 4000
        );

        $this->dispatch('close-preregistration-modal');
        $this->resetPreregistrationForm();
        $this->resetPage();
    }

    public function editPreregistration(int $preregistrationId): void
    {
        $preregistration = $this->resolveManagedPreregistration($preregistrationId);

        if (! $preregistration->canBeManaged()) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'Solo puedes editar preregistros activos o vencidos que no hayan sido usados.',
                duration: 4000
            );

            return;
        }

        $this->preregistrationId = $preregistration->id;
        $this->preregistrationType = $preregistration->preregistration_type;
        $this->preregistrationPhone = $preregistration->phone;
        $this->preregistrationPlan = (string) $preregistration->plan_id;
        $this->preregistrationParentPolicy = $preregistration->parent_policy_id
            ? (string) $preregistration->parent_policy_id
            : null;
        $this->preregistrationSalesUser = (string) $preregistration->sales_user_id;

        $this->resetErrorBag();
        $this->dispatch('open-preregistration-modal');
    }

    public function promptPreregistrationCancellation(int $preregistrationId): void
    {
        $preregistration = $this->resolveManagedPreregistration($preregistrationId);

        if (! $preregistration->canBeManaged()) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'Ese preregistro ya no se puede cancelar.',
                duration: 4000
            );

            return;
        }

        $this->preregistrationToCancelId = $preregistration->id;
        $this->preregistrationToCancelPhone = $preregistration->phone;
        $this->dispatch('open-preregistration-cancel-modal');
    }

    public function cancelPreregistration(PolicyPreregistrationService $service): void
    {
        if (! $this->preregistrationToCancelId) {
            return;
        }

        try {
            $preregistration = $this->resolveManagedPreregistration($this->preregistrationToCancelId);
            $service->cancelInvitation($preregistration, Auth::user());
        } catch (InvalidArgumentException $exception) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: $exception->getMessage(),
                duration: 4000
            );

            return;
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Preregistro cancelado correctamente.',
            duration: 4000
        );

        $this->dispatch('close-preregistration-cancel-modal');
        $this->preregistrationToCancelId = null;
        $this->preregistrationToCancelPhone = '';

        if ($this->preregistrationId === $preregistration->id) {
            $this->resetPreregistrationForm();
        }
    }

    public function resetPreregistrationForm(): void
    {
        $this->resetValidation([
            'preregistrationType',
            'preregistrationPhone',
            'preregistrationPlan',
            'preregistrationParentPolicy',
            'preregistrationSalesUser',
        ]);

        $this->preregistrationId = null;
        $this->preregistrationType = PolicyPreregistration::TYPE_INDIVIDUAL_POLICY;
        $this->preregistrationPhone = '';
        $this->preregistrationPlan = null;
        $this->preregistrationParentPolicy = null;
        $this->preregistrationSalesUser = Auth::user()?->profile === 'Sales'
            ? (string) Auth::id()
            : null;
    }

    public function clearPreregistrationFilters(): void
    {
        $this->filterPreregistrationPhone = '';
        $this->filterPreregistrationPlan = '';
        $this->filterPreregistrationType = '';
        $this->filterPreregistrationStatus = '';
        $this->preregistrationPerPage = 10;
        $this->resetPage();
    }

    public function updatingFilterPreregistrationPhone(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPreregistrationPlan(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPreregistrationType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPreregistrationStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPreregistrationPerPage(): void
    {
        $this->resetPage();
    }

    public function maybeOpenPrefilledPreregistrationModal(): void
    {
        if (! $this->shouldOpenPrefilledPreregistrationModal) {
            return;
        }

        $this->shouldOpenPrefilledPreregistrationModal = false;
        $this->dispatch('open-preregistration-modal');
    }

    #[Computed]
    public function selectedGroupPolicy(): ?Policy
    {
        if ($this->preregistrationType !== PolicyPreregistration::TYPE_GROUP_MEMBER || ! $this->preregistrationParentPolicy) {
            return null;
        }

        return $this->preregistrationGroupPolicies->firstWhere('id', (int) $this->preregistrationParentPolicy);
    }

    #[Computed]
    public function selectedGroupPolicyCapacity(): ?array
    {
        if (! $this->selectedGroupPolicy) {
            return null;
        }

        return app(GroupPolicyCapacityService::class)->summary(
            $this->selectedGroupPolicy,
            $this->preregistrationId
        );
    }

    public function updatedPreregistrationType(string $value): void
    {
        if ($value === PolicyPreregistration::TYPE_GROUP_MEMBER) {
            $this->preregistrationPlan = $this->selectedGroupPolicy?->plan_id
                ? (string) $this->selectedGroupPolicy->plan_id
                : null;

            return;
        }

        $this->preregistrationParentPolicy = null;
    }

    public function updatedPreregistrationParentPolicy(?string $value): void
    {
        if ($this->preregistrationType !== PolicyPreregistration::TYPE_GROUP_MEMBER) {
            return;
        }

        $groupPolicy = $value
            ? $this->preregistrationGroupPolicies->firstWhere('id', (int) $value)
            : null;

        $this->preregistrationPlan = $groupPolicy?->plan_id
            ? (string) $groupPolicy->plan_id
            : null;
    }

    private function loadPreregistrationOptions(): void
    {
        $this->preregistrationPlans = Plan::query()
            ->where('type', 'Individual')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->preregistrationFilterPlans = Plan::query()
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $user = Auth::user();

        $this->preregistrationParentPolicies = Policy::query()
            ->with(['user:id,name,company_id', 'user.company:id,name'])
            ->whereNull('parent_policy_id')
            ->when($user?->profile === 'Sales', function (Builder $query) use ($user) {
                $query->where('sales_user_id', $user->id);
            })
            ->whereHas('plan', function ($query) {
                $query->where('type', 'Individual');
            })
            ->orderBy('number')
            ->get();

        $this->preregistrationGroupPolicies = Policy::query()
            ->with(['plan:id,name', 'user:id,name,company_id', 'user.company:id,name'])
            ->whereNull('parent_policy_id')
            ->where('type', 'Group')
            ->where('status', '!=', 'Cancelled')
            ->when($user?->profile === 'Sales', function (Builder $query) use ($user) {
                $query->where('sales_user_id', $user->id);
            })
            ->orderBy('number')
            ->get();

        $this->preregistrationSalesAgents = User::query()
            ->where('profile', 'Sales')
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($user?->profile === 'Sales') {
            $this->preregistrationSalesUser = (string) $user->id;
        }
    }

    private function preregistrationsQuery(): Builder
    {
        $user = Auth::user();

        return PolicyPreregistration::query()
            ->with([
                'plan:id,name',
                'policy:id,policy_preregistration_id,number',
                'salesUser:id,name',
                'parentPolicy:id,number',
            ])
            ->when($user?->profile === 'Sales', function (Builder $query) use ($user) {
                $query->where('sales_user_id', $user->id);
            })
            ->when(filled($this->filterPreregistrationPhone), function (Builder $query) {
                $query->where('phone', 'like', '%'.trim($this->filterPreregistrationPhone).'%');
            })
            ->when(filled($this->filterPreregistrationPlan), function (Builder $query) {
                $query->where('plan_id', $this->filterPreregistrationPlan);
            })
            ->when(filled($this->filterPreregistrationType), function (Builder $query) {
                $query->where('preregistration_type', $this->filterPreregistrationType);
            })
            ->when(filled($this->filterPreregistrationStatus), function (Builder $query) {
                match ($this->filterPreregistrationStatus) {
                    'active' => $query->whereNull('cancelled_at')
                        ->whereNull('used_at')
                        ->where('expires_at', '>', now()),
                    'expired' => $query->whereNull('cancelled_at')
                        ->whereNull('used_at')
                        ->where('expires_at', '<=', now()),
                    'used' => $query->whereNotNull('used_at'),
                    'cancelled' => $query->whereNotNull('cancelled_at'),
                    default => null,
                };
            })
            ->latest();
    }

    private function resolveManagedPreregistration(int $preregistrationId): PolicyPreregistration
    {
        return PolicyPreregistration::query()
            ->when(Auth::user()?->profile === 'Sales', function (Builder $query) {
                $query->where('sales_user_id', Auth::id());
            })
            ->findOrFail($preregistrationId);
    }

    private function resolveSalesUser(int $salesUserId): User
    {
        $query = User::query()
            ->whereKey($salesUserId)
            ->where('profile', 'Sales');

        if (Auth::user()?->profile === 'Sales') {
            $query->whereKey(Auth::id());
        }

        return $query->firstOrFail();
    }

    private function resolvePreregistrationPlanId(array $validated): int
    {
        if ($validated['preregistrationType'] === PolicyPreregistration::TYPE_GROUP_MEMBER) {
            $groupPolicy = $this->selectedGroupPolicy;

            if (! $groupPolicy) {
                throw ValidationException::withMessages([
                    'preregistrationParentPolicy' => 'Selecciona una poliza colectiva valida.',
                ]);
            }

            return (int) $groupPolicy->plan_id;
        }

        return (int) $validated['preregistrationPlan'];
    }

    private function applyRequestPrefill(): void
    {
        $requestedType = request()->query('type');

        if (in_array($requestedType, [
            PolicyPreregistration::TYPE_INDIVIDUAL_POLICY,
            PolicyPreregistration::TYPE_GROUP_MEMBER,
        ], true)) {
            $this->preregistrationType = $requestedType;
        }

        $requestedParentPolicy = request()->query('parent_policy');

        if ($this->preregistrationType === PolicyPreregistration::TYPE_GROUP_MEMBER && filled($requestedParentPolicy)) {
            $groupPolicy = $this->preregistrationGroupPolicies->firstWhere('id', (int) $requestedParentPolicy);

            if ($groupPolicy) {
                $this->preregistrationParentPolicy = (string) $groupPolicy->id;
                $this->preregistrationPlan = (string) $groupPolicy->plan_id;
                $this->shouldOpenPrefilledPreregistrationModal = request()->query('open') === '1';
            }
        }
    }
}
