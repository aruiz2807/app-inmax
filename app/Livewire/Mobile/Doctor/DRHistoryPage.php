<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Mobile\Doctor\NoShowConfirmationPage;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class DRHistoryPage extends Component
{
    public $upcomingAppointments = null;
    public $pastAppointments = null;
    public $appointmentId = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.history-page');
    }

    public function mount()
    {
        $this->loadAppointments();
    }

    public function loadAppointments()
    {
        $this->upcomingAppointments = Appointment::where([
                ['status', 'Booked'],
                ['doctor_id', Auth::user()->id],
            ])
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $this->pastAppointments = Appointment::where([
                ['status', '!=', 'Booked'],
                ['doctor_id', Auth::user()->id],
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    public function noshow($id)
    {
        $this->appointmentId = $id;

        //open modal
        $this->dispatch('open-noshow-modal');
    }

    public function confirmNoshow()
    {
        $appointment = Appointment::findOrFail($this->appointmentId);

        $appointment->update([
            'status' => 'No-show',
        ]);

        //close modal
        $this->dispatch('close-noshow-modal');

        session()->flash('appointment_noshow_id', $appointment->id);

        return $this->redirect(NoShowConfirmationPage::class);
    }

    public function attend($id)
    {
        return $this->redirectRoute('doctor.notes', ['appointment' => $id,]);
    }
}
