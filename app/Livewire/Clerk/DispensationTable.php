<?php

namespace App\Livewire\Clerk;

use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class DispensationTable extends PowerGridComponent
{
    public string $tableName = 'dispensationTable';
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
                ->includeViewOnTop('livewire.clerk.dispensation-date-presets'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Appointment::query()
            ->leftJoin('appointment_notes', 'appointment_notes.appointment_id', '=', 'appointments.id')
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->select('appointments.*', 'appointment_notes.id as appointment_note_id', 'appointment_notes.created_at as appointment_note_date')
            ->with(['user.policy', 'doctor.user'])
            ->where('doctors.type', 'Doctor')
            ->whereNotNull('status_prescription')
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
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('appointment_note_id', function ($row): string {
                $appointmentNoteId = data_get($row, 'appointment_note_id');

                if (! is_numeric($appointmentNoteId)) {
                    return '-';
                }

                return str_pad((string) (int) $appointmentNoteId, 5, '0', STR_PAD_LEFT);
            })
            ->add('patient_name', fn ($row): string => data_get($row, 'user.name', 'Sin paciente'))
            ->add('patient_display', function ($row): string {
                return Blade::render(
                    '<div class="flex items-center gap-2"><x-ui.avatar size="sm" icon="user" color="teal" :src="$photo" circle /><span class="font-medium">{{ $name }}</span></div>',
                    [
                        'photo' => data_get($row, 'user.photo_url'),
                        'name' => data_get($row, 'user.name', 'Sin paciente'),
                    ]
                );
            })
            ->add('membership_status', fn ($row): string => (string) data_get($row, 'user.policy.status', 'Inactive'))
            ->add('membership_status_badge', function ($row): string {
                $status = (string) data_get($row, 'user.policy.status', 'Inactive');
                $color = $status === 'Active' ? 'green' : 'gray';
                $text = $status === 'Active' ? 'Activa' : 'Inactiva';

                return Blade::render(
                    '<x-ui.badge variant="outline" :color="$color" pill>{{ $text }}</x-ui.badge>',
                    ['color' => $color, 'text' => $text]
                );
            })
            ->add('membership_number', fn ($row): string => data_get($row, 'user.policy.number', '-'))
            ->add('prescriber_doctor', fn ($row): string => data_get($row, 'doctor.user.name', 'Sin médico'))
            ->add('prescriber_doctor_display', function ($row): string {
                $rating = max(0, min(5, (int) data_get($row, 'doctor.rating', 0)));
                $stars = str_repeat('★', $rating).str_repeat('☆', 5 - $rating);

                return Blade::render(
                    '<div class="flex items-start gap-2"><div class="pt-0.5"><x-ui.avatar size="sm" icon="user" color="teal" :src="$photo" circle /></div><div class="leading-tight"><p class="font-medium">{{ $name }}</p><p class="text-yellow-500 text-xs mt-1">{{ $stars }}</p></div></div>',
                    [
                        'photo' => data_get($row, 'doctor.user.photo_url'),
                        'name' => data_get($row, 'doctor.user.name', 'Sin médico'),
                        'stars' => $stars,
                    ]
                );
            })
            ->add('appointment_at_formatted', function ($row): string {
                $date = data_get($row, 'appointment_note_date');

                return Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('d/m/Y H:i');
            })
            ->add('status_label', function ($row): string {
                return match ((string) data_get($row, 'status_prescription')) {
                    'Filled' => 'Surtida',
                    'Partial' => 'Surtida Parcial',
                    'Cancelled' => 'Vencida',
                    default => 'Pendiente',
                };
            })
            ->add('status_badge', function ($row): string {
                return match ((string) data_get($row, 'status_prescription')) {
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
            Column::make('Receta', 'appointment_note_id', 'appointment_note_id')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy('appointment_notes.id', $direction)),

            Column::make('Nombre paciente', 'patient_display', 'patient_name')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\User::query()
                        ->select('name')
                        ->whereColumn('users.id', 'appointments.user_id')
                        ->limit(1),
                    $direction
                )),

            Column::make('Membresía', 'membership_status_badge', 'membership_status')
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\Policy::query()
                        ->select('status')
                        ->whereColumn('policies.user_id', 'appointments.user_id')
                        ->limit(1),
                    $direction
                ))
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('No. Membresía', 'membership_number')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\Policy::query()
                        ->select('number')
                        ->whereColumn('policies.user_id', 'appointments.user_id')
                        ->limit(1),
                    $direction
                )),

            Column::make('Médico prescriptor', 'prescriber_doctor_display', 'prescriber_doctor')
                ->searchable()
                ->sortable()
                ->sortUsing(fn (Builder $query, string $direction) => $query->orderBy(
                    \App\Models\Doctor::query()
                        ->select('users.name')
                        ->join('users', 'users.id', '=', 'doctors.user_id')
                        ->whereColumn('doctors.id', 'appointments.doctor_id')
                        ->limit(1),
                    $direction
                )),

            Column::make('Fecha consulta', 'appointment_at_formatted', 'date')
                ->sortable(),

            Column::make('Estatus', 'status_badge', 'status_prescription')
                ->sortable(),

            Column::action('Opciones'),
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('show_details')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="eye" variant="outline" class="w-5 h-5"/><span>Detalle</span></div>'))
                ->id()
                ->class('text-sky-600 hover:bg-sky-50 px-2 py-1 rounded transition-colors')
                ->dispatch('openPrescription', ['appointmentId' => (int) data_get($row, 'id')]),
        ];
    }
}
