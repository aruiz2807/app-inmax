<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Forms\DoctorNotesForm;
use App\Livewire\Mobile\Doctor\NotesConfirmationPage;
use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\PolicyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppCloudApiService;

class DRNotesPage extends Component
{
    use WithFileUploads;

    public DoctorNotesForm $form;
    public $appointment;
    public $services;
    public $subtotal;
    public $user_payment;
    public $commision;
    public $total;
    public $user;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.notes-page');
    }

    public function mount($appointment)
    {
        $this->user = Auth::user();
        $this->appointment = Appointment::findOrFail($appointment);
        $this->services = AppointmentService::where('appointment_id', $this->appointment->id)->get();
        $this->form->isDoctor = $this->user->doctor->type === DoctorType::Doctor;

        foreach ($this->services as $service)
        {
            $this->form->services[$service->id] = false;
            $this->form->attachments[$service->id] = null;
        }
    }

    public function save()
    {
        //open modal
        $this->dispatch('open-notes-modal');
    }

    public function updatedSubtotal($value)
    {
        $subtotal = floatval(str_replace(',', '', $value));
        $discount = round($subtotal * ($this->appointment->doctor->discount/100), 2);
        $this->user_payment = number_format($subtotal - $discount, 2);
        $this->commision = number_format($subtotal * ($this->appointment->doctor->commission / 100), 2);
        $this->total = number_format($subtotal - $discount - floatval(str_replace(',', '', $this->commision)), 2);
    }

    public function confirmNotes()
    {
        try
        {
            $note = $this->form->store($this->appointment->id);
        }
        catch (ValidationException $e)
        {
            $this->setErrorBag($e->validator->errors());
        }

        //reedem the corresponding cupon and mark the appointment as 'completed'
        $this->redeem();

        //close modal
        $this->dispatch('close-notes-modal');

        session()->flash('appointment_note_id', $note);

        return $this->redirect(NotesConfirmationPage::class);
    }

    public function redeem()
    {
        $policy = $this->appointment->user->policy;
        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;

        foreach($this->services as $service)
        {
            // Skip if NOT marked as done
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            $benefit = PolicyService::where([
                ['policy_id', $policyId],
                ['service_id', $service->service_id],
            ])->first();

            if($benefit)
            {
                if($service->covered)
                {
                    $benefit->increment('used');
                }
                else
                {
                    $benefit->increment('extra');
                }
            }
        }

        $this->subtotal = str_replace(',', '', $this->subtotal);

        $this->appointment->update([
            'subtotal' => $this->subtotal ?: '0.00',
            'doctor_id' => $this->user->doctor->id,
            'status' => 'Completed',
        ]);

        //enviar whatsapp
        $service = app(WhatsAppCloudApiService::class);
        $params = [$this->appointment->user->name, $this->appointment->note->created_at->format('d/m/Y'), $this->appointment->doctor->user->name ];
        $this->sendWhatsApp($service, $this->appointment->user->phone, $params);
    }

    public function sendWhatsApp(WhatsAppCloudApiService $service, $to, $params)
    {
        $setting = WhatsAppSetting::query()->firstOrFail();

        $result = $service->sendTemplateMessage(
            setting: $setting,
            to: '+52'.$to,
            templateName: $setting->appointment_completed_template_name,
            languageCode: $setting->default_language ?: 'es_MX',
            parameters: $params,
            buttonUrlParameters: [],
        );
    }
}
