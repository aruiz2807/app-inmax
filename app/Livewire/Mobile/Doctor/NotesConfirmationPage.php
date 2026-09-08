<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;

class NotesConfirmationPage extends Component
{
    public $note;
    public bool $hasReceptionistAssigned = false;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.notes-confirmation-page');
    }

    public function mount()
    {
        $noteId = session('appointment_note_id');

        if (!$noteId) {
            return redirect()->route('doctor.home');
        }

        $this->note = AppointmentNote::with(['appointment.prescriptions.medication', 'appointment.doctor.user', 'appointment.doctor.specialty', 'appointment.user', 'appointment.services'])
            ->where('id', $noteId)
            ->firstOrFail();

        $this->hasReceptionistAssigned = $this->note->appointment->doctor
            ?->staff()
            ->where('profile', 'Receptionist')
            ->exists() ?? false;
    }

    public function schedule()
    {
        return $this->redirectRoute('doctor.schedule', [
            'appointment' => $this->note->appointment->id,
        ]);
    }

    public function print()
    {
        $pdf = Pdf::loadView('pdf.prescription', [
            'note' => $this->note,
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.mx'
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "prescription-{$this->note->id}.pdf"
        );
    }

    public function print_ticket(?string $type = null)
    {
        $type = in_array($type ?? $this->ticketType, ['partner', 'member'], true)
            ? ($type ?? $this->ticketType)
            : 'partner';

        $pdf = Pdf::loadView('pdf.ticket', [
            'note' => $this->note,
            'subtotal' => $this->getSubtotalProperty(),
            'coupon_discount' => $this->getCouponDiscountProperty(),
            'payment' => $this->getPaymentProperty(),
            'commision' => $this->getCommissionProperty(),
            'total' => $this->getTotalProperty(),
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.mx',
            'type' => $type,
        ])->setPaper([0, 0, 226, 567], 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "ticket-{$this->note->id}.pdf"
        );
    }

    public function getSubtotalProperty()
    {
        return $this->formatServiceAmount('subtotal');
    }

    public function getCouponDiscountProperty()
    {
        return $this->formatServiceAmount('coupon_discount');
    }

    public function getDiscountProperty()
    {
        return number_format($this->note->appointment->subtotal * ($this->note->appointment->doctor->discount / 100), 2);
    }

    public function getPaymentProperty()
    {
        return $this->formatServiceAmount('user_payment');
    }

    public function getTotalProperty()
    {
        return $this->formatServiceAmount('total');
    }

    public function getCommissionProperty()
    {
        return $this->formatServiceAmount('commission');
    }

    private function formatServiceAmount(string $column): string
    {
        $appointment = $this->note->appointment;
        $completedServices = $appointment->services->where('status', 'Completed');

        $hasServiceAmounts = $completedServices->contains(fn ($service) => $service->{$column} !== null);
        $amount = $hasServiceAmounts
            ? $completedServices->sum($column)
            : $appointment->{$column};

        return number_format((float) $amount, 2);
    }
}
