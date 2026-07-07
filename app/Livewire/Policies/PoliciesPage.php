<?php

namespace App\Livewire\Policies;

use App\Models\Policy;
use App\Models\PolicyLegalInformation;
use App\Models\PolicyService;
use App\Services\Auth\PinSetupTokenService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public $percentage = 0;
    public $total_included;
    public $total_used;
    public $total_extra;
    public $services = [];
    public $policy_type;
    public $icon;

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

    #[On('showStatus')]
    public function showStatus(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        $policyId = $policy->type === 'Member' 
            ? $policy->parent_policy_id 
            : $policy->id;
        
            $this->services = PolicyService::with([
                'service', 
                'coupon',
            ])
            ->where('policy_id', $policyId)
            ->get();

        $sum = PolicyService::where('policy_id', $policyId)
            ->selectRaw('SUM(included) as total_included, SUM(used) as total_used, SUM(extra) as total_extra')
            ->first();

        $this->policy_type = $policy->type === 'Individual' ? 'Individual' : 'Colectiva';
        $this->icon = $policy->type === 'Individual' ? 'user' : 'user-group';
        $this->total_included = $sum->total_included ?? 0;
        $this->total_used = $sum->total_used ?? 0;
        $this->total_extra = $sum->total_extra ?? 0;
        $this->percentage = $this->total_included > 0 ? round(($this->total_used / $this->total_included) * 100) : 0;

        $this->dispatch('open-status-modal');
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
                content: 'La membresía ya estaba activa. No se envio un nuevo enlace de PIN.',
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
                ($result['whatsapp']['ok'] ?? false) => 'Membresía activada y enlace de PIN enviado por WhatsApp.',
                ($result['whatsapp']['attempted'] ?? false) => 'Membresía activada. No se pudo enviar WhatsApp, enlace disponible para prueba.',
                default => 'Membresía activada. Falta configurar WhatsApp, enlace disponible para prueba.',
            };
        }
        else
        {
            $policy->update([
                'status' => 'Active',
            ]);

            $content = '¡Membresía reactivada exitosamente!';
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
            content: '¡Membresía desactivada exitosamente!',
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
            content: '¡Membresía cancelada exitosamente!',
            duration: 4000
        );

        $this->dispatch('close-cancel-modal');
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    #[On('printPolicy')]
    public function print(int $policyId)
    {
        $legalInfo = PolicyLegalInformation::where('policy_id', $policyId)->first();

        $pdf = Pdf::loadView('pdf.contract', [
            'info' => $legalInfo,
        ])->setPaper('legal', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "contract-{$policyId}.pdf"
        );
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
