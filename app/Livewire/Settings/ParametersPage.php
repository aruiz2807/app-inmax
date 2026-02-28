<?php

namespace App\Livewire\Settings;

use App\Models\Parameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ParametersPage extends Component
{
    use WithPagination;

    public string $type = '';

    public string $parameterKey = '';

    public string $description = '';

    public string $value = '';

    public string $filterType = '';

    public string $filterKey = '';

    public string $filterDescription = '';

    public string $filterValue = '';

    public int $perPage = 10;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.settings.parameters-page', [
            'parameters' => $this->parametersQuery()->paginate($this->perPage),
        ]);
    }

    public function saveParameter(): void
    {
        $validated = Validator::make([
            'type' => $this->type,
            'key' => $this->parameterKey,
            'description' => $this->description,
            'value' => $this->value,
        ], [
            'type' => ['required', 'string', 'max:50'],
            'key' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parameters', 'key')
                    ->where(fn ($query) => $query->where('type', trim($this->type))),
            ],
            'description' => ['required', 'string', 'max:120'],
            'value' => ['required', 'string', 'max:120'],
        ], [
            'key.unique' => 'Ya existe un parametro con la misma combinacion Tipo + Clave.',
        ])->validate();

        Parameter::query()->create([
            'type' => trim($validated['type']),
            'key' => trim($validated['key']),
            'description' => trim($validated['description']),
            'value' => trim($validated['value']),
        ]);

        $this->resetForm();
        $this->resetPage();

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Parametro guardado correctamente.',
            duration: 4000
        );
    }

    public function clearFilters(): void
    {
        $this->filterType = '';
        $this->filterKey = '';
        $this->filterDescription = '';
        $this->filterValue = '';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterKey(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDescription(): void
    {
        $this->resetPage();
    }

    public function updatingFilterValue(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->type = '';
        $this->parameterKey = '';
        $this->description = '';
        $this->value = '';
    }

    private function parametersQuery(): Builder
    {
        return Parameter::query()
            ->when(filled($this->filterType), function (Builder $query) {
                $query->where('type', 'like', '%'.trim($this->filterType).'%');
            })
            ->when(filled($this->filterKey), function (Builder $query) {
                $query->where('key', 'like', '%'.trim($this->filterKey).'%');
            })
            ->when(filled($this->filterDescription), function (Builder $query) {
                $query->where('description', 'like', '%'.trim($this->filterDescription).'%');
            })
            ->when(filled($this->filterValue), function (Builder $query) {
                $query->where('value', 'like', '%'.trim($this->filterValue).'%');
            })
            ->orderBy('type')
            ->orderBy('key');
    }
}
