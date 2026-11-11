<?php

namespace App\Livewire\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class AppointmentsTable extends PowerGridComponent
{
    public string $tableName = 'appointmentsTable';
    public string $tab = 'booked';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount()
                ->pageName('page_' . $this->tab),
        ];
    }

    public function datasource(): Builder
    {
        return Appointment::query()
            ->select('appointments.*')
            ->leftJoin('users as patients', 'patients.id', '=', 'appointments.user_id')
            ->leftJoin('doctors', 'doctors.id', '=', 'appointments.doctor_id')
            ->leftJoin('users as doctor_users', 'doctor_users.id', '=', 'doctors.user_id')
            ->leftJoin('offices', 'offices.id', '=', 'appointments.office_id')
            ->leftJoin('specialties', 'specialties.id', '=', 'doctors.specialty_id')
            ->with(['user:id,name', 'doctor.user:id,name', 'doctor.specialty:id,name,service_id', 'doctor.specialty.service:id,name', 'office:id,name'])
            ->when($this->tab === 'booked', fn (Builder $query) => $query->where('appointments.status', AppointmentStatus::BOOKED->value))
            ->when($this->tab === 'completed', fn (Builder $query) => $query->whereIn('appointments.status', [
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::RESULTS_PENDING->value,
            ]))
            ->when($this->tab === 'cancelled', fn (Builder $query) => $query->whereIn('appointments.status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
                AppointmentStatus::REJECTED->value,
            ]))
            ->where('appointments.status', '!=', AppointmentStatus::VIRTUAL->value);
    }

    public function relationSearch(): array
    {
        return [
            'user' => [ 
                'name', 
            ],
            'doctor' => [
                'user' => ['name']
            ],
            'office' => [
                'name',
            ]
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('user_id')
            ->add('user_name', fn ($model) => e($model->user?->name ?? ''))
            ->add('doctor_id')
            ->add('doctor_office', fn ($model) => e($model->doctor?->user?->name ?? $model->office?->name ?? ''))
            ->add('specialty', fn ($model) => e($model->doctor?->specialty?->name ?? ''))
            ->add('date_formatted', fn ($model) => $model->date?->format('d/m/Y'))
            ->add('time')
            ->add('time_formatted', fn ($model) => $model->time?->format('H:i A'))
            ->add('created_at_formatted', fn ($model) => $model->created_at?->format('d/m/Y H:i A'))
            ->add('status_badge', fn ($model) => Blade::render('<x-status-badge status="' . ($model->status?->value ?? '') . '" />'))
            ->add('created_at');
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),

            Column::make('Miembro', 'user_name', 'patients.name')
                ->sortable(),

            Column::make('Medico/Consultorio', 'doctor_office')
                ->sortable()
                ->sortUsing(function (Builder $query, string $direction) {
                    $direction = in_array(strtolower($direction), ['asc', 'desc'], true) ? $direction : 'asc';

                    return $query->orderByRaw("COALESCE(doctor_users.name, offices.name) {$direction}");
                }),

            Column::make('Especialidad', 'specialty', 'specialties.name')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::make('Fecha', 'date_formatted', 'date')
                ->sortable(),

            Column::make('Hora', 'time_formatted', 'time')
                ->sortable()
                ->searchable(),

            Column::make('Fecha creación', 'created_at_formatted', 'created_at')
                ->sortable()
                ->searchable(),

            Column::make('Estatus', 'status_badge', 'status')
                ->sortable()
                ->searchable(),

            Column::make('Fecha registro', 'created_at_formatted', 'created_at')
                ->sortable()
                ->hidden(isHidden: true, isForceHidden: false),

            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            //Filter::datepicker('date'),
        ];
    }

    public function actions(Appointment $row): array
    {
        $isCompleted = $row->status === AppointmentStatus::COMPLETED;
        $hasPrescriptions = $row->prescriptions()->exists();

        $buttons = [
            Button::add('edit')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="pencil-square" variant="outline" class="w-5 h-5"/><span>Editar</span></div>'))
                ->id()
                ->class('text-teal-600 hover:bg-teal-50 px-2 py-1 rounded transition-colors')
                ->dispatch('editAppointment', ['appointmentId' => $row->id]),

            Button::add('cancel')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="x-circle" variant="outline" class="w-5 h-5"/><span>Cancelar</span></div>'))
                ->id()
                ->class('text-orange-600 hover:bg-orange-50 px-2 py-1 rounded transition-colors')
                ->dispatch('cancelAppointment', ['appointmentId' => $row->id]),

            Button::add('history')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="clipboard-document-list" variant="outline" class="w-5 h-5"/><span>Historial</span></div>'))
                ->id()
                ->class('text-blue-600 hover:bg-blue-50 px-2 py-1 rounded transition-colors')
                ->dispatch('historyAppointment', ['appointmentId' => $row->id]),

            Button::add('order')
                ->slot(Blade::render('<div class="flex items-center gap-2"><x-ui.icon name="document-arrow-down" variant="outline" class="w-5 h-5"/><span>Orden</span></div>'))
                ->id()
                ->class('text-gray-600 hover:bg-gray-50 px-2 py-1 rounded transition-colors')
                ->dispatch('orderAppointment', ['appointmentId' => $row->id]),

            $isCompleted && $hasPrescriptions
                ? Button::add('print')
                    ->slot(Blade::render('<div class="inline-flex items-center gap-2"><x-ui.icon name="document" variant="outline" class="w-5 h-5"/><span>Receta</span></div>'))
                    ->id()
                    ->class('w-[120px] text-neutral-700 hover:bg-neutral-100 px-2 py-1 rounded transition-colors')
                    ->dispatch('doctorPrintAppointment', ['appointmentId' => $row->id])
                : null,
        ];

        return array_values(array_filter($buttons, fn (?object $button) => $button !== null));
    }

    public function actionRules(): array
    {

        return [

            Rule::button('edit')
                ->when(fn($model) => in_array($model->status, [
                    \App\Enums\AppointmentStatus::COMPLETED,
                    \App\Enums\AppointmentStatus::CANCELLED,
                    \App\Enums\AppointmentStatus::NO_SHOW,
                    \App\Enums\AppointmentStatus::REJECTED,
                ]))
                ->hide(),

            Rule::button('cancel')
                ->when(fn($model) => in_array($model->status, [
                    \App\Enums\AppointmentStatus::COMPLETED,
                    \App\Enums\AppointmentStatus::CANCELLED,
                    \App\Enums\AppointmentStatus::NO_SHOW,
                    \App\Enums\AppointmentStatus::REJECTED,
                ]))
                ->hide(),

            Rule::button('order')
                ->when(fn($model) => is_null($model->requested_by_user_id))
                ->hide(),

        ];
    }

    #[On('doctorPrintAppointment')]
    public function print(int $appointmentId)
    {
        $note = Appointment::findOrFail($appointmentId)->note;

        $pdf = Pdf::loadView('pdf.prescription', [
            'note' => $note,
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.mx'
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'receta.pdf'
        );
    }
}
