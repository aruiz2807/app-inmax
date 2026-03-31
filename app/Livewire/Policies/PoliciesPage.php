<?php

namespace App\Livewire\Policies;

use App\Models\Policy;
use App\Services\Auth\PinSetupTokenService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PoliciesPage extends Component
{
    use WithFileUploads;

    public ?int $policyId = null;

    public ?string $policyType = null;

    public bool $newMember = false;

    public string $policy_number = '';

    public string $policy_user_name = '';

    public ?string $lastPinSetupUrl = null;

    public ?string $lastPinSetupName = null;

    public ?string $lastPinSetupPhone = null;

    public $payment_method = null;
    public $payment_reference = null;
    public $payment_attachment = null;
    public $reactivation = false;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.policies-page');
    }

    #[On('editPolicy')]
    public function edit(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyType = $policy->plan->type;
        $this->policyId = $policyId;

        $this->dispatch('open-policy-modal');
    }

    #[On('addMember')]
    public function addMember(int $policyId): void
    {
        $this->policyType = 'Individual';
        $this->policyId = $policyId;
        $this->newMember = true;

        $this->dispatch('open-policy-modal');
    }

    #[On('activatePolicy')]
    public function activate(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;
        $this->payment_method = $policy->payment_method;
        $this->payment_reference = $policy->payment_reference;

        if($policy->payment_method){
            $this->reactivation = true;
        }

        $this->dispatch('open-activation-modal');
    }

    #[On('deactivatePolicy')]
    public function deactivate(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        $this->dispatch('open-deactivation-modal');
    }

    #[On('cancelPolicy')]
    public function cancel(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        $this->dispatch('open-cancel-modal');
    }

    public function confirmActivation(PinSetupTokenService $tokenService): void
    {
        $policy = Policy::query()->findOrFail($this->policyId);

        if ($policy->status === 'Active') {
            $this->dispatch(
                'notify',
                type: 'info',
                content: 'La poliza ya estaba activa. No se envio un nuevo enlace de PIN.',
                duration: 4000
            );

            $this->dispatch('close-activation-modal');

            return;
        }

        if(!$this->reactivation)
        {
            $start = Carbon::now()->addDays(5);
            $end = Carbon::now()->addDays(5)->addYear();
            $path = null;
            $originalName = null;

            if($this->payment_attachment)
            {
                $file = $this->payment_attachment;
                $path = $file->store('attachments');
                $originalName = $file->getClientOriginalName();
            }

            $policy->update([
                'status' => 'Active',
                'start_date' => $start,
                'end_date' => $end,
                'payment_method' => $this->payment_method,
                'payment_reference' => $this->payment_reference,
                'payment_file_path' => $path,
                'payment_file_name' => $originalName,
            ]);

            $purpose = $policy->user->pin_set_at
                ? PinSetupTokenService::PURPOSE_RESET
                : PinSetupTokenService::PURPOSE_ACTIVATION;

            $result = $tokenService->generateSetupLink($policy->user, Auth::user(), $purpose);

            $this->lastPinSetupUrl = $result['url'];
            $this->lastPinSetupName = $policy->user->name;
            $this->lastPinSetupPhone = $policy->user->phone;

            $content = match (true) {
                ($result['whatsapp']['ok'] ?? false) => 'Poliza activada y enlace de PIN enviado por WhatsApp.',
                ($result['whatsapp']['attempted'] ?? false) => 'Poliza activada. No se pudo enviar WhatsApp, enlace disponible para prueba.',
                default => 'Poliza activada. Falta configurar WhatsApp, enlace disponible para prueba.',
            };
        }
        else
        {
            $policy->update([
                'status' => 'Active',
            ]);

            $content = 'Poliza reactivada exitosamente!';
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: $content,
            duration: 4000
        );

        $this->payment_method = null;
        $this->payment_reference = null;
        $this->payment_attachment = null;

        $this->dispatch('close-activation-modal');
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmDeactivation(): void
    {
        $policy = Policy::query()->findOrFail($this->policyId);

        $policy->update([
            'status' => 'Inactive',
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Poliza desactivada exitosamente!',
            duration: 4000
        );

        $this->dispatch('close-deactivation-modal');
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmCancel(): void
    {
        $policy = Policy::query()->findOrFail($this->policyId);

        $policy->update([
            'status' => 'Cancelled',
            'end_date' => Carbon::now(),
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Poliza cancelada exitosamente!',
            duration: 4000
        );

        $this->dispatch('close-cancel-modal');
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function selectType(string $type): void
    {
        $this->policyType = $type;
    }

    public function resetForm(): void
    {
        $this->policyId = null;
        $this->policyType = null;
        $this->newMember = false;
    }
}
