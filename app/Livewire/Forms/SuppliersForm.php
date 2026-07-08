<?php

namespace App\Livewire\Forms;

use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SuppliersForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:13')]
    public ?string $rfc = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $address = null;

    #[Validate('nullable|string|max:20')]
    public ?string $phone = null;

    #[Validate('nullable|email|max:255')]
    public ?string $email = null;

    public function store(): Supplier
    {
        $this->validate();

        return Supplier::create($this->only(['name', 'rfc', 'address', 'phone', 'email']));
    }

    public function set(Supplier $supplier): void
    {
        $this->name = $supplier->name;
        $this->rfc = $supplier->rfc;
        $this->address = $supplier->address;
        $this->phone = $supplier->phone;
        $this->email = $supplier->email;
    }

    public function update(int $supplierId): void
    {
        Validator::make([
            'name' => $this->name,
            'rfc' => $this->rfc,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ])->validate();

        $supplier = Supplier::findOrFail($supplierId);

        $supplier->update([
            'name' => $this->name,
            'rfc' => $this->rfc,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
        ]);
    }
}
