<?php

namespace App\Livewire\Forms;

use App\Enums\MedicationMovementType;
use App\Models\Medication;
use App\Models\MedicationMovement;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class MedicationAdjustmentForm extends Form
{
    #[Validate('required|in:IN,OUT')]
    public $type = 'IN';

    #[Validate('required|integer|min:1')]
    public $quantity = 1;

    #[Validate('nullable|string|max:1000')]
    public $adjustment_comment = '';

    /**
    * Store an inventory adjustment movement in the DB.
    */
    public function store(int $medicationId, int $userId)
    {
        $this->validate();

        $medication = Medication::findOrFail($medicationId);

        if ($this->type === MedicationMovementType::OUT->value && $this->quantity > $medication->existences) {
            throw ValidationException::withMessages([
                'adjustmentForm.quantity' => 'La cantidad a descontar no puede ser mayor a la existencia actual.',
            ]);
        }

        MedicationMovement::create([
            'medication_id' => $medication->id,
            'type' => $this->type,
            'adjustment' => true,
            'adjustment_comment' => $this->adjustment_comment ?: null,
            'quantity' => $this->quantity,
            'reference' => 'Ajuste manual de existencias',
            'prescription_id' => null,
            'medication_purchase_id' => null,
            'user_id' => $userId,
        ]);
    }

    /**
    * Sets default values when opening the adjustment form.
    */
    public function set(Medication $medication)
    {
        $this->reset();
    }
}
