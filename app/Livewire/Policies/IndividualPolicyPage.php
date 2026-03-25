<?php

namespace App\Livewire\Policies;

use App\Livewire\Forms\IndividualPolicyForm;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\User;
use App\Services\Auth\PinSetupTokenService;
use App\Services\Policies\GroupPolicyCapacityService;
use App\Services\Policies\IndividualPolicyRegistrationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class IndividualPolicyPage extends Component
{
    use WithFileUploads;

    public IndividualPolicyForm $form;
    public ?int $policyId = null;
    public $plans = [];
    public $policies = [];
    public $sales_agents = [];
    public $member = false;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.individual-policy-page');
    }

    public function mount($policyId, $newMember)
    {
        if($policyId && !$newMember)
        {
            $this->policyId = $policyId;
            $this->form->set(Policy::find($policyId));
        }

        if(!$newMember)
        {
            $this->plans = Plan::orderBy('name')->where([
                ['type', 'Individual'],
                ['status', 'Active'],
            ])->get();

            $this->policies = Policy::with('user:id,name')
                ->whereNull('parent_policy_id')
                ->where('id', '!=', $policyId) // exclude this policy
                ->whereHas('plan', function ($query) {
                    $query->where('type', 'individual'); // filter only 'individual' plans
            })->get();
        }
        else
        {
            $this->plans = Plan::orderBy('name')->where([
                ['type', 'Group'],
                ['status', 'Active'],
            ])->get();

            $this->policies = Policy::where('id', $policyId)
                ->with(['user:id,name,company_id', 'user.company:id,name'])
                ->get();

            $this->form->member(Policy::find($policyId));
            $this->member = $newMember;
        }

        $this->form->sales_user = Auth::user()?->profile === 'Sales' ? Auth::user()->id : null;

        $this->sales_agents = User::where('profile', 'Sales')
            ->select('id', 'name')
            ->get();
    }

    public function save(
        IndividualPolicyRegistrationService $registrationService,
        PinSetupTokenService $tokenService,
        GroupPolicyCapacityService $groupPolicyCapacityService
    )
    {
        if($this->policyId and !$this->member)
        {
            $this->form->update($this->policyId);

            $content = 'Poliza almacenada exitosamente!';
        }
        else
        {
            if ($this->member && $this->form->parent_policy) {
                try {
                    $groupPolicyCapacityService->assertHasAvailableSlot((int) $this->form->parent_policy);
                } catch (InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        'form.parent_policy' => $exception->getMessage(),
                    ]);
                }
            }

            $policy = $this->form->store($registrationService);
            $result = $tokenService->generateSetupLink(
                $policy->user,
                Auth::user(),
                PinSetupTokenService::PURPOSE_ACTIVATION
            );

            $content = match (true) {
                ($result['whatsapp']['ok'] ?? false) => 'Poliza creada y enlace de PIN enviado por WhatsApp.',
                ($result['whatsapp']['attempted'] ?? false) => 'Poliza creada. No se pudo enviar WhatsApp, enlace de PIN generado.',
                default => 'Poliza creada. Falta configurar WhatsApp para enviar el enlace de PIN.',
            };
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content: $content,
            duration: 4000
        );

        //close modal
        $this->dispatch('close-policy-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');

        //clear form
        $this->resetForm();
    }

    #[Computed]
    public function age()
    {
        if(!$this->form->birth)
        {
            return null;
        }

        try
        {
            return Carbon::parse($this->form->birth)->age;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->form->addingMember = false;
        $this->member = false;
        $this->policyId = null;
    }
}
