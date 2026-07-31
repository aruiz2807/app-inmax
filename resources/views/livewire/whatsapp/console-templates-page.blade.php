<div class="space-y-4">
    <x-slot name="header">
        Plantillas consola WhatsApp
    </x-slot>

    <x-ui.card size="full">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <x-ui.heading level="h3" size="sm">
                    Plantillas para consola
                </x-ui.heading>
                <p class="mt-2 text-sm text-slate-500">
                    Alta de plantillas aprobadas en Meta para reactivar conversaciones fuera de la ventana de 24 horas.
                </p>
            </div>

            <x-ui.modal.trigger id="whatsapp-console-template-modal" wire:click="resetForm">
                <x-ui.button color="teal" icon="plus-circle">
                    Nueva plantilla
                </x-ui.button>
            </x-ui.modal.trigger>
        </div>
    </x-ui.card>

    <x-ui.card size="full">
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div
                class="grid grid-cols-[1.3fr_1fr_8rem_7rem_7rem] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <span>Plantilla</span>
                <span>Meta</span>
                <span>Idioma</span>
                <span>Variables</span>
                <span>Estado</span>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse ($templates as $template)
                    <div wire:key="whatsapp-console-template-{{ $template->id }}"
                        class="grid grid-cols-1 gap-3 px-4 py-4 text-sm lg:grid-cols-[1.3fr_1fr_8rem_7rem_7rem] lg:items-center">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $template->name }}</p>
                            @if ($template->example_text)
                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                                    {{ $template->example_text }}</p>
                            @endif
                        </div>

                        <div class="truncate text-slate-600">
                            {{ $template->meta_template_name }}
                        </div>

                        <div class="text-slate-600">
                            {{ $template->language_code }}
                        </div>

                        <div class="text-slate-600">
                            {{ count($template->body_variables ?? []) }} body /
                            {{ count($template->button_variables ?? []) }} boton
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <x-ui.badge :color="$template->is_active ? 'emerald' : 'slate'" size="sm" pill>
                                {{ $template->is_active ? 'Activa' : 'Inactiva' }}
                            </x-ui.badge>

                            <x-ui.button type="button" size="sm" icon="pencil-square" variant="outline"
                                color="teal" wire:click="edit({{ $template->id }})">
                                Editar
                            </x-ui.button>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-slate-500">
                        No hay plantillas de consola registradas.
                    </div>
                @endforelse
            </div>
        </div>
    </x-ui.card>

    <x-ui.modal id="whatsapp-console-template-modal" animation="fade" width="4xl"
        heading="{{ $templateId ? 'Editar plantilla' : 'Nueva plantilla' }}"
        description="Configura el nombre exacto en Meta, idioma, ejemplo y variables que llenara el operador."
        x-on:close-whatsapp-console-template-modal.window="$data.close()"
        x-on:open-whatsapp-console-template-modal.window="$data.open()">
        <form wire:submit="save">
            <x-ui.fieldset label="Datos Meta">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.field required>
                        <x-ui.label>Nombre interno</x-ui.label>
                        <x-ui.input wire:model="name" placeholder="Contacto cliente" />
                        <x-ui.error name="name" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Nombre plantilla Meta</x-ui.label>
                        <x-ui.input wire:model="metaTemplateName" placeholder="contacto_cliente" />
                        <x-ui.error name="metaTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Idioma</x-ui.label>
                        <x-ui.input wire:model="languageCode" placeholder="es_MX" />
                        <x-ui.error name="languageCode" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Estado</x-ui.label>
                        <div class="pt-2">
                            <x-ui.switch wire:model.live="isActive" :checked="$isActive" color="teal"
                                label="Plantilla activa" />
                        </div>
                        <x-ui.error name="isActive" />
                    </x-ui.field>
                </div>

                <x-ui.field>
                    <x-ui.label>Ejemplo para consola</x-ui.label>
                    <x-ui.textarea wire:model="exampleText" rows="3"
                        placeholder="Hola Juan, queremos dar seguimiento a tu solicitud..." />
                    <x-ui.error name="exampleText" />
                </x-ui.field>
            </x-ui.fieldset>

            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                @include('livewire.whatsapp.partials.console-template-variable-editor', [
                    'title' => 'Variables body',
                    'property' => 'bodyVariables',
                    'addMethod' => 'addBodyVariable',
                    'removeMethod' => 'removeBodyVariable',
                ])

                @include('livewire.whatsapp.partials.console-template-variable-editor', [
                    'title' => 'Variables boton URL',
                    'property' => 'buttonVariables',
                    'addMethod' => 'addButtonVariable',
                    'removeMethod' => 'removeButtonVariable',
                ])
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancelar
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
