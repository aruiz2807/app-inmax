<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Mobile\Doctor\NoShowConfirmationPage;
use App\Models\Appointment;
use App\Models\Parameter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DRHistoryPage extends Component
{
    public $upcomingAppointments = null;
    public $pastAppointments = null;
    public $appointmentId = null;
    public bool $isMobileDevice = true;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $tab = 'pending';

    public function render()
    {
        $view = $this->isMobileDevice
            ? 'livewire.mobile.doctor.history-page'
            : 'livewire.doctor.appointments-page';

        $layout = $this->isMobileDevice ? 'layouts.mobile' : 'layouts.app';

        return view($view)->layout($layout);
    }

    public function mount()
    {
        $this->isMobileDevice = $this->detectMobileDevice();

        $desktopVersionEnabled = Parameter::where('type', 'SITE')->where('key', 'Doctor_VersionDesktop')->first()->value == 'Activa';
        //$desktopVersionEnabled ? $this->isMobileDevice = false : $this->isMobileDevice = true;

        if (! $this->isMobileDevice) {
            $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
            $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
        }

        $this->loadAppointments();
    }

    protected function detectMobileDevice()
    {
        $forcedDevice = request()->query('device');

        if ($forcedDevice === 'mobile') {
            return true;
        }

        if ($forcedDevice === 'desktop') {
            return false;
        }

        $userAgent = strtolower((string) request()->userAgent());

        return preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent) === 1;
    }

    public function loadAppointments()
    {
        $user = Auth::user();
        $offices = $user->doctor->offices()->pluck('offices.id');

        $upcomingQuery = Appointment::where(function ($query) use ($user, $offices) {
                $query->where('doctor_id', $user->doctor->id)
                ->orWhereIn('office_id', $offices);
            })
            ->with(['user.policy', 'doctor.user', 'office'])
            ->where('status', \App\Enums\AppointmentStatus::BOOKED)
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('time');

        $pastQuery = Appointment::where('doctor_id', $user->doctor->id)
            ->with(['user.policy', 'doctor.user', 'office'])
            ->whereIn('status', [\App\Enums\AppointmentStatus::COMPLETED, \App\Enums\AppointmentStatus::CANCELLED, \App\Enums\AppointmentStatus::NO_SHOW])
            ->orderByDesc('date')
            ->orderByDesc('time');

        if (filled($this->dateFrom)) {
            $upcomingQuery->whereDate('date', '>=', $this->dateFrom);
            $pastQuery->whereDate('date', '>=', $this->dateFrom);
        }

        if (filled($this->dateTo)) {
            $upcomingQuery->whereDate('date', '<=', $this->dateTo);
            $pastQuery->whereDate('date', '<=', $this->dateTo);
        }

        $this->upcomingAppointments = $upcomingQuery->get();
        $this->pastAppointments = $pastQuery->get();
    }

    public function updatedDateFrom(): void
    {
        $this->normalizeDateRange();
        $this->loadAppointments();
    }

    public function updatedDateTo(): void
    {
        $this->normalizeDateRange();
        $this->loadAppointments();
    }

    public function clearDateRange(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->loadAppointments();
    }

    public function applyPreset(string $preset): void
    {
        if ($preset === 'last7') {
            $this->dateFrom = Carbon::today()->subDays(6)->toDateString();
            $this->dateTo = Carbon::today()->toDateString();
            $this->loadAppointments();
            return;
        }

        if ($preset === 'month') {
            $this->dateFrom = Carbon::today()->startOfMonth()->toDateString();
            $this->dateTo = Carbon::today()->endOfMonth()->toDateString();
            $this->loadAppointments();
        }
    }

    protected function normalizeDateRange(): void
    {
        if (filled($this->dateFrom) && filled($this->dateTo) && $this->dateFrom > $this->dateTo) {
            $this->dateTo = $this->dateFrom;
        }
    }

    #[On('openDoctorNoshowModal')]
    public function openDoctorNoshowModal(int $appointmentId): void
    {
        $this->noshow($appointmentId);
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'upcoming', 'past', 'cancelled'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->dispatch('doctorAppointmentsTabChanged', tab: $tab);
    }

    public function getUpcomingCountProperty(): int
    {
        return (int) $this->upcomingAppointments?->count();
    }

    public function getCompletedCountProperty(): int
    {
        return (int) $this->pastAppointments
            ?->where('status', \App\Enums\AppointmentStatus::COMPLETED)
            ->count();
    }

    public function getCancelledCountProperty(): int
    {
        return (int) $this->pastAppointments
            ?->whereIn('status', [
                \App\Enums\AppointmentStatus::CANCELLED,
                \App\Enums\AppointmentStatus::NO_SHOW,
            ])
            ->count();
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
            'status' => \App\Enums\AppointmentStatus::NO_SHOW,
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
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.com'
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
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.com'
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "prescription-{$note->id}.pdf"
        );
    }
}
