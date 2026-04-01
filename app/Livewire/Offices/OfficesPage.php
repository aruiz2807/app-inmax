<?php

namespace App\Livewire\Offices;

use App\Livewire\Forms\OfficesForm;
use App\Models\Doctor;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class OfficesPage extends Component
{
    public OfficesForm $form;
    public ?int $officeId = null;
    public $specialties = [];
    public $types = [];
    public $availableDoctors = [];
    public $slotMarkers = [];
    public $slotTime = '';

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.offices.offices-page');
    }

    public function mount()
    {
        $this->availableDoctors = Doctor::query()
            ->where('specialty_id', 1)
            ->with('user')
            ->orderBy('id')
            ->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-office-modal');
    }

    public function addSlotMarker()
    {
        if (!$this->slotTime) {
            $this->addError('slotMarkers', 'Selecciona una hora antes de agregarla.');

            return;
        }

        try {
            $time = Carbon::createFromFormat('H:i', $this->slotTime)->format('H:i');
        } catch (\Throwable $exception) {
            $this->addError('slotMarkers', 'La hora seleccionada no es válida.');

            return;
        }

        $this->resetValidation('slotMarkers');
    $this->slotMarkers[] = $time;
    $this->slotMarkers = $this->normalizeMarkers(collect($this->slotMarkers));
        $this->slotTime = '';

        $this->syncSlotsFromMarkers();
    }

    public function addDoctor(int $doctorId)
    {
        if (!in_array($doctorId, array_map('intval', $this->form->selectedDoctors), true)) {
            $this->form->selectedDoctors[] = $doctorId;
        }

        $this->form->selectedDoctors = array_values(array_unique(array_map('intval', $this->form->selectedDoctors)));
    }

    public function removeDoctor(int $doctorId)
    {
        $this->form->selectedDoctors = array_values(array_filter(
            $this->form->selectedDoctors,
            fn ($id) => (int) $id !== $doctorId
        ));
    }

    public function removeSlotMarker(int $index)
    {
        unset($this->slotMarkers[$index]);

        $this->slotMarkers = $this->normalizeMarkers(collect(array_values($this->slotMarkers)));
        $this->resetValidation('slotMarkers');
        $this->syncSlotsFromMarkers();
    }

    #[On('editOffice')]
    public function edit($officeId)
    {
        $office = Office::find($officeId);

        $this->form->set($office);
        $this->slotMarkers = $this->buildMarkersFromSlots($this->form->slots);
        $this->syncSlotsFromMarkers();
        $this->officeId = $officeId;

        //open modal
        $this->dispatch('open-office-modal');
    }

    public function save()
    {
        $this->syncSlotsFromMarkers();

        if($this->officeId)
        {
            $this->form->update($this->officeId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Consultorio almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-office-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-officesTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->form->selectedDoctors = [];
        $this->form->slots = [];
        $this->slotMarkers = [];
        $this->slotTime = '';
        $this->officeId = null;
        $this->resetValidation();
    }

    private function syncSlotsFromMarkers(): void
    {
        $this->form->slots = $this->normalizeMarkers(collect($this->slotMarkers));
    }

    private function buildMarkersFromSlots(array $slots): array
    {
        if (empty($slots)) {
            return [];
        }

        return $this->normalizeMarkers(collect($slots));
    }

    private function normalizeMarkers(Collection $markers): array
    {
        $normalized = $markers
            ->map(function (string $time) {
                try {
                    return Carbon::createFromFormat('H:i', $time)->format('H:i');
                } catch (\Throwable $exception) {
                    try {
                        return Carbon::createFromFormat('h:i A', $time)->format('H:i');
                    } catch (\Throwable $innerException) {
                        return null;
                    }
                }
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        usort($normalized, fn (string $left, string $right) => strcmp($left, $right));

        return $normalized;
    }

}
