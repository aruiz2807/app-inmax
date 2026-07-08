<?php

namespace App\Livewire\Medications;

use App\Livewire\Forms\MedicationsForm;
use App\Livewire\Forms\MedicationAdjustmentForm;
use App\Models\Medication;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class MedicationsPage extends Component
{
    public MedicationsForm $form;
    public MedicationAdjustmentForm $adjustmentForm;
    public ?int $medicationId = null;
    public ?Medication $medication = null;
    public ?string $selectedMedicationName = null;
    public array $selectedMedicationMovements = [];

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.medications.medications-page');
    }

    #[On('editMedication')]
    public function edit($medicationId)
    {
        $medication = Medication::find($medicationId);

        $this->form->set($medication);
        $this->medicationId = $medicationId;

        //open modal
        $this->dispatch('open-medication-modal');
    }

    public function save()
    {
        if($this->medicationId)
        {
            $this->form->update($this->medicationId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'¡Medicamento almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-medication-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-medicationsTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->medicationId = null;
    }

    #[On('adjustMedication')]
    public function adjust($medicationId)
    {
        $medication = Medication::find($medicationId);

        if (! $medication) {
            return;
        }

        $this->adjustmentForm->set($medication);
        $this->medicationId = $medicationId;
        $this->medication = $medication;

        //open modal
        $this->dispatch('open-adjustment-modal');
    }

    public function incrementQuantity()
    {
        $this->adjustmentForm->quantity++;
    }

    public function decrementQuantity()
    {
        $this->adjustmentForm->quantity--;
    }

    public function saveAdjustment()
    {
        if (! $this->medicationId) {
            return;
        }

        $userId = auth()->id();

        if (! $userId) {
            return;
        }

        $this->adjustmentForm->store(
            medicationId: $this->medicationId,
            userId: (int) $userId,
        );

        $this->medication = Medication::find($this->medicationId);

        $this->dispatch('notify',
            type: 'success',
            content: 'Ajuste de existencias aplicado exitosamente.',
            duration: 4000
        );

        $this->dispatch('close-adjustment-modal');
        $this->dispatch('pg:eventRefresh-medicationsTable');

        $this->resetAdjustmentForm();
    }

    public function resetAdjustmentForm()
    {
        $this->adjustmentForm->reset();
        $this->medicationId = null;
        $this->medication = null;
    }

    #[On('showMedicationMovements')]
    public function openMovements($medicationId)
    {
        $medication = Medication::query()
            ->with([
                'movements' => fn ($query) => $query->with('user')->latest(),
            ])
            ->find($medicationId);

        if (! $medication) {
            return;
        }

        $this->selectedMedicationName = $medication->name;
        $this->selectedMedicationMovements = $medication->movements
            ->map(fn ($movement) => [
                'id' => $movement->id,
                'created_at' => $movement->created_at?->format('d/m/Y H:i') ?? '-',
                'type' => $movement->type,
                'type_label' => $movement->type === 'IN' ? 'Entrada' : 'Salida',
                'quantity' => (int) $movement->quantity,
                'reference' => $movement->reference ?: '-',
                'adjustment_comment' => $movement->adjustment_comment ?: '-',
                'user_name' => $movement->user?->name ?? 'Sistema',
            ])
            ->toArray();

        $this->dispatch('open-medication-movements-modal');
    }

    public function resetMovementDetails()
    {
        $this->selectedMedicationName = null;
        $this->selectedMedicationMovements = [];
    }
}
