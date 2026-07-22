<?php

namespace App\Livewire\Doctor;

use App\Enums\AppointmentStatus;
use App\Enums\DoctorType;
use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AppointmentsTable extends PowerGridComponent
{
    public string $tableName = 'doctorAppointmentsTable';
    public string $tab = 'pending';
    public string $sortField = 'date';
    public string $sortDirection = 'desc';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        parent::mount();

        $this->tab = 'upcoming';
        $this->dateFrom = Carbon::now()->startOfMonth()->toDateString();
        $this->dateTo = Carbon::now()->endOfMonth()->toDateString();
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.doctor.appointments-date-presets'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    #[On('doctorAppointmentsTabChanged')]
    public function onTabChanged(string $tab): void
    {
        $this->setTab($tab);
    }

    public function datasource(): Builder
    {
        $officeIds = Auth::user()->doctor
            ?->offices()
            ->pluck('offices.id')
            ->toArray() ?? [];

        $currentDoctorId = Auth::user()->doctor?->id;

        return Appointment::query()
            ->select('appointments.*')
            ->leftJoin('users as patients', 'patients.id', '=', 'appointments.user_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->leftJoin('users as doctor_users', 'doctor_users.id', '=', 'doctors.user_id')
            ->leftJoin('offices', 'offices.id', '=', 'appointments.office_id')
            ->with([
                'user:id,name',
                'user.policy:id,user_id,number',
                'doctor:id,user_id,type',
                'doctor.user:id,name',
                'office:id,name',
                'note:id,appointment_id',
                'services:id,appointment_id,covered',
            ])
            ->where(function (Builder $query) use ($currentDoctorId, $officeIds) {
                $query->where('appointments.doctor_id', $currentDoctorId);

                if (! empty($officeIds)) {
                    $query->orWhereIn('appointments.office_id', $officeIds);
                }
            })
            ->when($this->tab === 'upcoming', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::BOOKED->value))
            ->when($this->tab === 'past', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::COMPLETED->value))
            ->when($this->tab === 'cancelled', fn (Builder $query) => $query->whereIn('appointments.status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ]))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('appointments.date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('appointments.date', '<=', $this->dateTo));
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, [ 'upcoming', 'past', 'cancelled', 'all'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function applyPreset(string $preset): void
    {
        [$start, $end] = match ($preset) {
            'last7' => [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfMonth()->endOfDay()],
            default => [null, null],
        };

        if ($start && $end) {
            $this->dateFrom = $start->toDateString();
            $this->dateTo = $end->toDateString();
        }
    }

    public function clearDateRange(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function beforeSearch(string $field, ?string $search): ?string
    {
        if ($field !== 'status' || blank($search)) {
            return $search;
        }

        $normalized = strtolower(trim((string) $search));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);

        return match (true) {
            str_contains($normalized, 'solicit') => 'Requested',
            str_contains($normalized, 'agend') => 'Booked',
            str_contains($normalized, 'cancel') => 'Cancelled',
            str_contains($normalized, 'atendid') => 'Completed',
            str_contains($normalized, 'no se present') || str_contains($normalized, 'no-show') || str_contains($normalized, 'noshow') => 'No-show',
            default => $search,
        };
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('date_formatted', fn (Appointment $appointment) => $appointment->date?->format('d/m/Y'))
            ->add('time_formatted', fn (Appointment $appointment) => $appointment->time?->format('H:i'))
            ->add('patient_name', fn (Appointment $appointment) => e($appointment->user?->name ?? 'N/A'))
            ->add('membership_number', fn (Appointment $appointment) => e($appointment->user?->policy?->number ?? '-'))
            ->add('provider_name', fn (Appointment $appointment) => e($appointment->doctor?->user?->name ?? $appointment->office?->name ?? 'N/A'))
            ->add('status_badge', fn (Appointment $appointment) => Blade::render('<x-status-badge status="'.$appointment->status?->value.'" />'));
    }

    public function columns(): array
    {
        return [
            Column::make('Fecha', 'date_formatted', 'date')
                ->sortable(),

            Column::make('Hora', 'time_formatted', 'time')
                ->sortable(),

            Column::make('Paciente', 'patient_name', 'patients.name')
                ->searchable()
                ->sortable(),

            Column::make('Membresia', 'membership_number', 'patient_policies.number')
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Proveedor', 'provider_name', 'doctor_users.name')
                ->searchable()
                ->sortable(),

            Column::make('Estado', 'status_badge', 'status')
                ->searchable()
                ->sortable(),

            Column::action('Accion'),
        ];
    }

    public function actions(Appointment $row)
    {
        $status = $row->status instanceof AppointmentStatus
            ? $row->status
            : AppointmentStatus::tryFrom((string) $row->status);

        $isCompleted = $status === AppointmentStatus::COMPLETED;
        $isUpcoming = $status === AppointmentStatus::BOOKED;
        $isDoctor = $row->doctor?->type === DoctorType::Doctor;

        return [
            $isUpcoming
                ? Button::add('attend')
                    ->slot(Blade::render('<a href="'.route('doctor.notes', ['appointment' => $row->id]).'" class="inline-flex items-center gap-2"><x-ui.icon name="clipboard" variant="outline" class="w-5 h-5"/><span>Atender</span></a>'))
                    ->id()
                    ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                : Button::add('detail')
                    ->slot(Blade::render('<a href="'.route('history.notes', ['appointment' => $row->id]).'" class="inline-flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Detalle</span></a>'))
                    ->id()
                    ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors'),

            $isUpcoming
                ? Button::add('noshow')
                    ->slot(Blade::render('<div class="inline-flex items-center gap-2"><x-ui.icon name="eye-slash" variant="outline" class="w-5 h-5"/><span>No asistio</span></div>'))
                    ->id()
                    ->class('w-[111px] text-rose-600 hover:bg-rose-50 px-2 py-1 rounded transition-colors')
                    ->dispatch('openDoctorNoshowModal', ['appointmentId' => $row->id])
                : Button::add('record')
                    ->slot(Blade::render('<a href="'.route('doctor.record', ['user' => $row->user_id]).'" class="inline-flex items-center gap-2"><x-ui.icon name="clipboard-document-list" variant="outline" class="w-5 h-5"/><span>Historial</span></a>'))
                    ->id()
                    ->class('w-[120px] text-neutral-700 hover:bg-neutral-100 px-2 py-1 rounded transition-colors'),

            $isCompleted
                ? Button::add('schedule')
                    ->slot(Blade::render('<a href="'.route('doctor.schedule', ['appointment' => $row->id]).'" class="inline-flex items-center gap-2"><x-ui.icon name="calendar" variant="outline" class="w-5 h-5"/><span>Agendar</span></a>'))
                    ->id()
                    ->class('w-[120px] text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                : Button::add('schedule_disabled')
                    ->slot(Blade::render('<div class="inline-flex items-center gap-2 opacity-40 cursor-not-allowed"><x-ui.icon name="calendar" variant="outline" class="w-5 h-5"/><span>Agendar</span></div>'))
                    ->id()
                    ->class('w-[120px] text-neutral-500'),

            $isCompleted
                ? Button::add('print')
                    ->slot(Blade::render('<div class="inline-flex items-center gap-2"><x-ui.icon name="document" variant="outline" class="w-5 h-5"/><span>Receta</span></div>'))
                    ->id()
                    ->class('w-[120px] text-neutral-700 hover:bg-neutral-100 px-2 py-1 rounded transition-colors')
                    ->dispatch('doctorPrintAppointment', ['appointmentId' => $row->id])
                : Button::add('print_disabled')
                    ->slot(Blade::render('<div class="inline-flex items-center gap-2 opacity-40 cursor-not-allowed"><x-ui.icon name="document" variant="outline" class="w-5 h-5"/><span>Receta</span></div>'))
                    ->id()
                    ->class('w-[120px] text-neutral-500'),
        ];
    }

    #[On('doctorPrintAppointment')]
    public function print(int $appointmentId)
    {
        $note = Appointment::findOrFail($appointmentId)->note;

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
