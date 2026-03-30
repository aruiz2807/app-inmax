<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Mobile\Doctor\NoShowConfirmationPage;
use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $user = Auth::user();
        $offices = $user->doctor->offices()->pluck('offices.id');

        $this->upcomingAppointments = Appointment::where(function ($query) use ($user, $offices) {
                $query->where('doctor_id', $user->doctor->id)
                ->orWhereIn('office_id', $offices);
            })
            ->where('status', 'Booked')
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $this->pastAppointments = Appointment::where('doctor_id', $user->doctor->id)
            ->whereIn('status', ['Completed', 'Cancelled', 'No-show'])
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
        return $this->redirectRoute('doctor.notes', ['appointment' => $id]);
    }

    public function record($id)
    {
        return $this->redirectRoute('doctor.record', ['user' => $id]);
    }

    public function notes($id)
    {
        return $this->redirectRoute('history.notes', ['appointment' => $id]);
    }

    public function schedule($id)
    {
        return $this->redirectRoute('doctor.schedule', ['appointment' => $id]);
    }

    public function order($id)
    {
        $appointment = Appointment::findOrFail($id);

        $pdf = Pdf::loadView('pdf.order', [
            'appointment' => $appointment,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "order-{$appointment->id}.pdf"
        );
    }

    public function print($id)
    {
        $note = Appointment::findOrFail($id)->note;

        $pdf = Pdf::loadView('pdf.prescription', [
            'note' => $note,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "prescription-{$note->id}.pdf"
        );
    }
}
