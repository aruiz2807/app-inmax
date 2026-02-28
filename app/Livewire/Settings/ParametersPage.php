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

    public ?int $parameterId = null;

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
        $isEditing = $this->parameterId !== null;
        $type = trim($this->type);
        $key = trim($this->parameterKey);
        $description = trim($this->description);
        $value = trim($this->value);

        $uniqueRule = Rule::unique('parameters', 'key')
            ->where(fn ($query) => $query->where('type', $type));

        if ($this->parameterId !== null) {
            $uniqueRule->ignore($this->parameterId);
        }

        $validated = Validator::make([
            'type' => $type,
            'key' => $key,
            'description' => $description,
            'value' => $value,
        ], [
            'type' => ['required', 'string', 'max:50'],
            'key' => [
                'required',
                'string',
                'max:50',
                $uniqueRule,
            ],
            'description' => ['required', 'string', 'max:120'],
            'value' => ['required', 'string', 'max:120'],
        ], [
            'key.unique' => 'Ya existe un parametro con la misma combinacion Tipo + Clave.',
        ])->validate();

        if (! $isEditing) {
            Parameter::query()->create([
                'type' => $validated['type'],
                'key' => $validated['key'],
                'description' => $validated['description'],
                'value' => $validated['value'],
            ]);
        } else {
            $parameter = Parameter::query()->findOrFail($this->parameterId);
            $parameter->update([
                'type' => $validated['type'],
                'key' => $validated['key'],
                'description' => $validated['description'],
                'value' => $validated['value'],
            ]);
        }

        $this->resetForm();
        $this->resetPage();

        $this->dispatch(
            'notify',
            type: 'success',
            content: ! $isEditing
                ? 'Parametro guardado correctamente.'
                : 'Parametro actualizado correctamente.',
            duration: 4000
        );
    }

    public function editParameter(int $parameterId): void
    {
        $parameter = Parameter::query()->findOrFail($parameterId);

        $this->parameterId = $parameter->id;
        $this->type = $parameter->type;
        $this->parameterKey = $parameter->key;
        $this->description = $parameter->description;
        $this->value = $parameter->value;
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->resetErrorBag();
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
        $this->parameterId = null;
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
