<div>
    <form wire:submit="save">
        @include('livewire.policies.partials.group-collective-fields', [
            'statePath' => 'form',
            'readonly' => false,
        ])

        @include('livewire.policies.partials.group-representative-fields', [
            'statePath' => 'form',
            'age' => $this->age,
            'phoneReadonly' => false,
        ])

        @include('livewire.policies.partials.group-membership-fields', [
            'statePath' => 'form',
            'plans' => $plans,
            'salesAgents' => $sales_agents,
            'promoterReadonly' => filled($form->sales_user),
            'promoterName' => filled($form->sales_user) ? auth()->user()->name : '',
        ])

        <div class="w-full flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                Cancel
            </x-ui.button>

            <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                Guardar
            </x-ui.button>
        </div>
    </form>
</div>
