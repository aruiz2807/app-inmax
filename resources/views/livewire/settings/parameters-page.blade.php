<div>
    <x-slot name="header">
        {{ __('app.parameters') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Configuracion de parametros
            </x-ui.heading>
            <p class="mt-2">
                Administra parametros generales del sistema. La combinacion de Tipo + Clave es unica.
            </p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Nuevo parametro
            </x-ui.heading>

            <form wire:submit="saveParameter" class="pt-4">
                <x-ui.fieldset label="Datos del parametro">
                    <x-ui.field required>
                        <x-ui.label>Tipo</x-ui.label>
                        <x-ui.input wire:model="type" maxlength="50" placeholder="GENERAL" />
                        <x-ui.error name="type" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Clave</x-ui.label>
                        <x-ui.input wire:model="parameterKey" maxlength="50" placeholder="MAX_INTENTOS" />
                        <x-ui.error name="key" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Descripcion</x-ui.label>
                        <x-ui.input wire:model="description" maxlength="120" placeholder="Numero maximo de intentos permitidos" />
                        <x-ui.error name="description" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Valor</x-ui.label>
                        <x-ui.input wire:model="value" maxlength="120" placeholder="5" />
                        <x-ui.error name="value" />
                    </x-ui.field>
                </x-ui.fieldset>

                <div class="w-full flex justify-end gap-3 pt-4">
                    <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                        Guardar parametro
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Parametros registrados
            </x-ui.heading>

            <div class="pt-4 grid gap-2 md:grid-cols-5">
                <x-ui.field>
                    <x-ui.label>Filtro tipo</x-ui.label>
                    <x-ui.input wire:model.live.debounce.400ms="filterType" placeholder="Buscar tipo..." />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro clave</x-ui.label>
                    <x-ui.input wire:model.live.debounce.400ms="filterKey" placeholder="Buscar clave..." />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro descripcion</x-ui.label>
                    <x-ui.input wire:model.live.debounce.400ms="filterDescription" placeholder="Buscar descripcion..." />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Filtro valor</x-ui.label>
                    <x-ui.input wire:model.live.debounce.400ms="filterValue" placeholder="Buscar valor..." />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Registros por pagina</x-ui.label>
                    <x-ui.select wire:model.live="perPage">
                        <x-ui.select.option value="10">10</x-ui.select.option>
                        <x-ui.select.option value="25">25</x-ui.select.option>
                        <x-ui.select.option value="50">50</x-ui.select.option>
                    </x-ui.select>
                </x-ui.field>
            </div>

            <div class="pt-3 flex justify-end">
                <x-ui.button type="button" icon="x-circle" variant="outline" wire:click="clearFilters">
                    Limpiar filtros
                </x-ui.button>
            </div>

            <div class="pt-4 overflow-x-auto">
                <table class="min-w-full text-sm border border-neutral-200 dark:border-neutral-700 rounded-lg overflow-hidden">
                    <thead class="bg-neutral-100 dark:bg-neutral-800">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold">Tipo</th>
                            <th class="text-left px-3 py-2 font-semibold">Clave</th>
                            <th class="text-left px-3 py-2 font-semibold">Descripcion</th>
                            <th class="text-left px-3 py-2 font-semibold">Valor</th>
                            <th class="text-left px-3 py-2 font-semibold">Fecha alta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parameters as $parameter)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-3 py-2">{{ $parameter->type }}</td>
                                <td class="px-3 py-2">{{ $parameter->key }}</td>
                                <td class="px-3 py-2">{{ $parameter->description }}</td>
                                <td class="px-3 py-2">{{ $parameter->value }}</td>
                                <td class="px-3 py-2">{{ $parameter->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-neutral-600 dark:text-neutral-300">
                                    No hay parametros registrados con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-4">
                {{ $parameters->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
