<?php

namespace App\Livewire\Doctor;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class VirtualNotesTable extends PowerGridComponent
{
    public string $tableName = 'virtualNotesTable';
    public string $tab = 'pending';
    public string $sortField = 'date';
    public string $sortDirection = 'desc';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        parent::mount();

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

    public function datasource(): Builder
    {
        $doctorId = Auth::user()->doctor->id;

        return Appointment::query()
            ->select('appointments.*')
            ->leftJoin('users as patients', 'patients.id', '=', 'appointments.user_id')
            ->leftJoin('policies as patient_policies', 'patient_policies.user_id', '=', 'appointments.user_id')
            ->withCount('prescriptions')
            ->with(['user.policy'])
            ->where('appointments.doctor_id', $doctorId)
            ->where('appointments.status', \App\Enums\AppointmentStatus::VIRTUAL->value)
            ->when($this->tab === 'pending', fn (Builder $query) => $query->where('status_prescription', 'Pending'))
            ->when($this->tab === 'partial', fn (Builder $query) => $query->where('status_prescription', 'Partial'))
            ->when($this->tab === 'filled', fn (Builder $query) => $query->where('status_prescription', 'Filled'))
            ->when($this->tab === 'cancelled', fn (Builder $query) => $query->where('status_prescription', 'Cancelled'))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('appointments.date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('appointments.date', '<=', $this->dateTo));
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
        return [
            'user' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('date_formatted', fn ($row) => $row->date?->format('d/m/Y'))
            ->add('patient_display', function ($row) {
                return Blade::render(
                    '<div class="flex items-center gap-2"><x-ui.avatar size="sm" icon="user" color="teal" :src="$photo" circle /><span class="font-medium">{{ $name }}</span></div>',
                    [
                        'photo' => $row->user?->photo_url,
                        'name' => e($row->user?->name ?? 'Sin paciente'),
                    ]
                );
            })
            ->add('membership_number', fn ($row) => e($row->user?->policy?->number ?? '-'))
            ->add('prescriptions_count')
            ->add('status_badge', function ($row) {
                return match ((string) $row->status_prescription) {
                    'Filled' => '<span class="px-2 py-1 text-xs font-bold rounded-full text-green-700 bg-green-100">Surtida</span>',
                    'Partial' => '<span class="px-2 py-1 text-xs font-bold rounded-full text-blue-700 bg-blue-100">Surtida Parcial</span>',
                    'Cancelled' => '<span class="px-2 py-1 text-xs font-bold rounded-full text-red-700 bg-red-100">Vencida</span>',
                    default => '<span class="px-2 py-1 text-xs font-bold rounded-full text-yellow-700 bg-yellow-100">Pendiente</span>',
                };
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Fecha', 'date_formatted', 'date')
                ->sortable(),

            Column::make('Paciente', 'patient_display', 'patients.name')
                ->searchable()
                ->sortable(),

            Column::make('Membresia', 'membership_number', 'patient_policies.number')
                ->searchable()
                ->sortable(),

            Column::make('Medicamentos', 'prescriptions_count'),

            Column::make('Estatus', 'status_badge', 'status_prescription')
                ->sortable(),

            Column::action('Accion'),
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('view')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Ver</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('showVirtualNoteDetails', ['appointmentId' => $row->id]),
        ];
    }
}
