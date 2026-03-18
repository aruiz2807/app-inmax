<?php

namespace App\Livewire\Policies;

use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyPreregistration;
use App\Services\Auth\PolicyPreregistrationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class PolicyPreregistrationsPage extends Component
{
    use WithPagination;

    public string $preregistrationPhone = '';

    public ?string $preregistrationPlan = null;

    public ?string $preregistrationParentPolicy = null;

    public ?string $lastPreregistrationUrl = null;

    public ?string $lastPreregistrationPhone = null;

    public ?string $lastPreregistrationPlanName = null;

    public ?string $lastPreregistrationExpiresAt = null;

    public ?int $preregistrationId = null;

    public ?int $preregistrationToCancelId = null;

    public string $preregistrationToCancelPhone = '';

    public string $filterPreregistrationPhone = '';

    public string $filterPreregistrationPlan = '';

    public string $filterPreregistrationStatus = '';

    public int $preregistrationPerPage = 10;

    public Collection $preregistrationPlans;

    public Collection $preregistrationParentPolicies;

    public function mount(): void
    {
        $this->loadPreregistrationOptions();
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
            'preregistrationPhone' => ['required', 'digits:10', 'unique:users,phone'],
            'preregistrationPlan' => ['required'],
            'preregistrationParentPolicy' => ['nullable'],
        ], [
            'preregistrationPhone.unique' => 'Ya existe un usuario registrado con ese telefono.',
        ]);

        try {
            if (! $isEditing) {
                $result = $service->createInvitation(
                    Auth::user(),
                    $validated['preregistrationPhone'],
                    (int) $validated['preregistrationPlan'],
                    filled($validated['preregistrationParentPolicy'])
                        ? (int) $validated['preregistrationParentPolicy']
                        : null
                );
            } else {
                $preregistration = $this->resolveManagedPreregistration($this->preregistrationId);
                $result = $service->updateInvitation(
                    $preregistration,
                    Auth::user(),
                    $validated['preregistrationPhone'],
                    (int) $validated['preregistrationPlan'],
                    filled($validated['preregistrationParentPolicy'])
                        ? (int) $validated['preregistrationParentPolicy']
                        : null
                );
            }
        } catch (InvalidArgumentException $exception) {
            $field = match (true) {
                str_contains($exception->getMessage(), 'telefono') => 'preregistrationPhone',
                str_contains($exception->getMessage(), 'principal') => 'preregistrationParentPolicy',
                str_contains($exception->getMessage(), 'cobertura') => 'preregistrationPlan',
                default => 'preregistrationPhone',
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
        $this->preregistrationPhone = $preregistration->phone;
        $this->preregistrationPlan = (string) $preregistration->plan_id;
        $this->preregistrationParentPolicy = $preregistration->parent_policy_id
            ? (string) $preregistration->parent_policy_id
            : null;

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
            'preregistrationPhone',
            'preregistrationPlan',
            'preregistrationParentPolicy',
        ]);

        $this->preregistrationId = null;
        $this->preregistrationPhone = '';
        $this->preregistrationPlan = null;
        $this->preregistrationParentPolicy = null;
    }

    public function clearPreregistrationFilters(): void
    {
        $this->filterPreregistrationPhone = '';
        $this->filterPreregistrationPlan = '';
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

    public function updatingFilterPreregistrationStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPreregistrationPerPage(): void
    {
        $this->resetPage();
    }

    private function loadPreregistrationOptions(): void
    {
        $this->preregistrationPlans = Plan::query()
            ->where('type', 'Individual')
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
    }

    private function preregistrationsQuery(): Builder
    {
        $user = Auth::user();

        return PolicyPreregistration::query()
            ->with(['plan:id,name', 'policy:id,policy_preregistration_id,number', 'salesUser:id,name'])
            ->when($user?->profile === 'Sales', function (Builder $query) use ($user) {
                $query->where('sales_user_id', $user->id);
            })
            ->when(filled($this->filterPreregistrationPhone), function (Builder $query) {
                $query->where('phone', 'like', '%'.trim($this->filterPreregistrationPhone).'%');
            })
            ->when(filled($this->filterPreregistrationPlan), function (Builder $query) {
                $query->where('plan_id', $this->filterPreregistrationPlan);
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
}
