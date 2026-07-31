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
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <div class="min-w-[74rem]">
                <div
                    class="grid grid-cols-[minmax(22rem,1.5fr)_minmax(12rem,1fr)_6rem_8rem_8rem_7rem_8rem] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <span>Plantilla</span>
                    <span>Meta</span>
                    <span>Idioma</span>
                    <span>Encabezado</span>
                    <span>Variables</span>
                    <span>Estado</span>
                    <span class="text-right">Acciones</span>
                </div>

                <div class="divide-y divide-slate-200">
                    @forelse ($templates as $template)
                        <div wire:key="whatsapp-console-template-{{ $template->id }}"
                            class="grid grid-cols-[minmax(22rem,1.5fr)_minmax(12rem,1fr)_6rem_8rem_8rem_7rem_8rem] items-center gap-3 px-4 py-4 text-sm">
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
                                {{ match ($template->header_media_type) {
                                    'image' => 'Imagen',
                                    'video' => 'Video',
                                    'document' => 'PDF',
                                    default => 'Ninguno',
                                } }}
                            </div>

                            <div class="text-slate-600">
                                {{ count($template->body_variables ?? []) }} body /
                                {{ count($template->button_variables ?? []) }} boton
                            </div>

                            <div>
                                <x-ui.badge :color="$template->is_active ? 'emerald' : 'slate'" size="sm" pill>
                                    {{ $template->is_active ? 'Activa' : 'Inactiva' }}
                                </x-ui.badge>
                            </div>

                            <div class="flex justify-end">
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
                        <x-ui.label>Encabezado multimedia</x-ui.label>
                        <select
                            wire:model="headerMediaType"
                            class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15"
                        >
                            <option value="">Sin encabezado</option>
                            <option value="image">Imagen</option>
                            <option value="video">Video</option>
                            <option value="document">Documento PDF</option>
                        </select>
                        <x-ui.error name="headerMediaType" />
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
