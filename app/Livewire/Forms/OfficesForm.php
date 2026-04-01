<?php

namespace App\Livewire\Forms;

use App\Models\Office;
use App\Models\OfficeHour;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Form;

class OfficesForm extends Form
{
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required')]
    public $address = '';

    #[Validate('required|max:2048')]
    public $maps_url = '';

    #[Validate('nullable|string|max:20')]
    public $phone_number = '';

    #[Validate('required')]
    public $office_id = 1;

    public $selectedDoctors = [];
    public $slots = [];

    protected function rules()
    {
        return [
            'selectedDoctors' => 'array',
            'selectedDoctors.*' => 'integer|exists:doctors,id',
            'slots' => 'array',
            'slots.*' => 'string|max:8',
        ];
    }

    /**
    * Store the office in the DB.
    */
    public function store()
    {
        $this->validate();

        $office = Office::create([
            'name' => $this->name,
            'address' => $this->address,
            'maps_url' => $this->maps_url,
            'phone_number' => $this->phone_number ?: null,
        ]);

        $office->doctors()->sync($this->selectedDoctors);

        foreach ($this->slots as $slot) {
            OfficeHour::create([
                'office_id' => $office->id,
                'slot' => $this->formatSlotForStorage($slot),
            ]);
        }
    }

    /**
    * Sets the office to edit.
    */
    public function set(Office $office)
    {
        $this->name = $office->name;
        $this->address = $office->address;
        $this->maps_url = $office->maps_url;
        $this->phone_number = $office->phone_number ?? '';
        $this->selectedDoctors = $office->doctors()->pluck('doctors.id')->toArray();
        $this->slots = $office->officeHours()->pluck('slot')->toArray();
    }

    /**
    * Updates the office in the DB.
    */
    public function update($officeId)
    {
        $this->validate();

        $office = Office::find($officeId);

        if (!$office) {
            return;
        }

        $office->update([
            'name' => $this->name,
            'address' => $this->address,
            'maps_url' => $this->maps_url,
            'phone_number' => $this->phone_number ?: null,
        ]);

        $office->doctors()->sync($this->selectedDoctors);

        $office->officeHours()->delete();

        foreach ($this->slots as $slot) {
            OfficeHour::create([
                'office_id' => $office->id,
                'slot' => $this->formatSlotForStorage($slot),
            ]);
        }
    }

    private function formatSlotForStorage(string $slot): string
    {
        try {
            return Carbon::createFromFormat('H:i', $slot)->format('h:i A');
        } catch (\Throwable $exception) {
            try {
                return Carbon::createFromFormat('h:i A', $slot)->format('h:i A');
            } catch (\Throwable $innerException) {
                return $slot;
            }
        }
    }
}
