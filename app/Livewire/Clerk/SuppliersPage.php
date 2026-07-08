<?php

namespace App\Livewire\Clerk;

use App\Livewire\Forms\SuppliersForm;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class SuppliersPage extends Component
{
    public SuppliersForm $form;
    public ?int $supplierId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.clerk.suppliers-page');
    }

    #[On('editSupplier')]
    public function edit(int $supplierId): void
    {
        $supplier = Supplier::findOrFail($supplierId);

        $this->form->set($supplier);
        $this->supplierId = $supplierId;

        $this->dispatch('open-supplier-modal');
    }

    #[On('deleteSupplier')]
    public function delete(int $supplierId): void
    {
        Supplier::whereKey($supplierId)->delete();

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Proveedor eliminado exitosamente.',
            duration: 4000
        );

        $this->dispatch('pg:eventRefresh-suppliersTable');

        if ($this->supplierId === $supplierId) {
            $this->resetForm();
        }
    }

    public function save(): void
    {
        if ($this->supplierId) {
            $this->form->update($this->supplierId);
        } else {
            $this->form->store();
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Proveedor almacenado exitosamente.',
            duration: 4000
        );

        $this->dispatch('close-supplier-modal');
        $this->dispatch('pg:eventRefresh-suppliersTable');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->form->reset();
        $this->supplierId = null;
        $this->resetValidation();
    }
}
