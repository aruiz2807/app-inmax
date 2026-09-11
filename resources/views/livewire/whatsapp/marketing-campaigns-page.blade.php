<div class="space-y-4">
    <x-slot name="header">
        Campañas WhatsApp
    </x-slot>

    <x-ui.card size="full">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <x-ui.heading level="h3" size="sm">
                    Mercadotecnia WhatsApp
                </x-ui.heading>
                <p class="mt-2 text-sm text-slate-500">
                    Carga Excel/CSV, selecciona una plantilla aprobada y envia campañas por WhatsApp con variables dinamicas.
                </p>
            </div>

            <x-ui.modal.trigger id="whatsapp-marketing-campaign-modal" wire:click="resetForm">
                <x-ui.button color="teal" icon="plus-circle">
                    Nueva campaña
                </x-ui.button>
            </x-ui.modal.trigger>
        </div>
    </x-ui.card>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <x-ui.card size="full">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <div class="min-w-[74rem]">
                    <div class="grid grid-cols-[minmax(16rem,1.3fr)_minmax(12rem,1fr)_7rem_8rem_8rem_8rem_11rem] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <span>Campaña</span>
                        <span>Plantilla</span>
                        <span>Estado</span>
                        <span>Validos</span>
                        <span>Enviados</span>
                        <span>Fallidos</span>
                        <span class="text-right">Acciones</span>
                    </div>

                    <div class="divide-y divide-slate-200">
                        @forelse ($campaigns as $campaign)
                            <div wire:key="whatsapp-marketing-campaign-{{ $campaign->id }}"
                                class="grid grid-cols-[minmax(16rem,1.3fr)_minmax(12rem,1fr)_7rem_8rem_8rem_8rem_11rem] items-center gap-3 px-4 py-4 text-sm">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $campaign->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $campaign->created_at?->format('d/m/Y H:i') }}
                                    </p>
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-slate-700">{{ $campaign->template?->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $campaign->template?->meta_template_name }}</p>
                                </div>

                                <div>
                                    <x-ui.badge :color="match ($campaign->status) {
                                        'completed' => 'emerald',
                                        'sending', 'queued' => 'amber',
                                        'cancelled' => 'slate',
                                        default => 'blue',
                                    }" size="sm" pill>
                                        {{ $campaign->statusLabel() }}
                                    </x-ui.badge>
                                </div>

                                <div class="text-slate-600">
                                    {{ $campaign->valid_recipients }} / {{ $campaign->total_recipients }}
                                </div>

                                <div class="text-slate-600">
                                    {{ $campaign->sent_count }}
                                </div>

                                <div class="text-slate-600">
                                    {{ $campaign->failed_count }}
                                </div>

                                <div class="flex justify-end gap-2">
                                    <x-ui.button type="button" size="sm" variant="outline" icon="eye"
                                        wire:click="selectCampaign({{ $campaign->id }})">
                                        Ver
                                    </x-ui.button>

                                    <x-ui.button type="button" size="sm" color="teal" icon="paper-airplane"
                                        wire:click="sendCampaign({{ $campaign->id }})">
                                        Enviar
                                    </x-ui.button>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-10 text-center text-sm text-slate-500">
                                No hay campañas registradas.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card size="full">
            @if ($selectedCampaign)
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <x-ui.heading level="h3" size="sm">
                            {{ $selectedCampaign->name }}
                        </x-ui.heading>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $selectedCampaign->template?->name }} · {{ $selectedCampaign->template?->language_code }}
                        </p>
                    </div>

                    <x-ui.button type="button" size="sm" variant="outline" icon="x-mark" wire:click="closeCampaignDetail" />
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Destinatarios validos</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $selectedCampaign->valid_recipients }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Invalidos/duplicados</p>
                        <p class="text-lg font-semibold text-slate-900">{{ $selectedCampaign->invalid_recipients }}</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-3">
                        <p class="text-xs text-emerald-700">Enviados</p>
                        <p class="text-lg font-semibold text-emerald-900">{{ $selectedCampaign->sent_count }}</p>
                    </div>
                    <div class="rounded-xl bg-red-50 p-3">
                        <p class="text-xs text-red-700">Fallidos</p>
                        <p class="text-lg font-semibold text-red-900">{{ $selectedCampaign->failed_count }}</p>
                    </div>
                    <div class="rounded-xl bg-cyan-50 p-3">
                        <p class="text-xs text-cyan-700">Respondieron</p>
                        <p class="text-lg font-semibold text-cyan-900">{{ $selectedCampaign->responded_recipients_count }}</p>
                    </div>
                    <div class="rounded-xl bg-indigo-50 p-3">
                        <p class="text-xs text-indigo-700">Botones / directas</p>
                        <p class="text-lg font-semibold text-indigo-900">
                            {{ $selectedCampaign->button_responses_count }} / {{ $selectedCampaign->direct_responses_count }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.button type="button" size="sm" color="teal" icon="paper-airplane"
                        wire:click="sendCampaign({{ $selectedCampaign->id }})">
                        Enviar pendientes
                    </x-ui.button>

                    <x-ui.button type="button" size="sm" variant="outline" icon="arrow-path"
                        wire:click="sendCampaign({{ $selectedCampaign->id }}, true)">
                        Reintentar fallidos
                    </x-ui.button>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-900">Detalle de destinatarios</p>
                    <p class="text-xs text-slate-500">Respondidos primero</p>
                </div>

                <div class="mt-2 max-h-[32rem] overflow-auto rounded-xl border border-slate-200">
                    <div class="divide-y divide-slate-100">
                        @forelse ($selectedRecipients as $recipient)
                            <div class="p-3 text-xs" wire:key="marketing-recipient-detail-{{ $recipient->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">
                                            {{ $recipient->customer_name ?: 'Sin nombre' }}
                                        </p>
                                        <p class="mt-0.5 text-slate-500">{{ $recipient->phone_normalized }}</p>
                                    </div>

                                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-600">
                                        {{ $recipient->statusLabel() }}
                                    </span>
                                </div>

                                @if ($recipient->responded_at)
                                    <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-emerald-600 px-2 py-1 text-[11px] font-semibold text-white">
                                                {{ $recipient->responseTypeLabel() }}
                                            </span>
                                            <span class="text-[11px] text-emerald-800">
                                                {{ $recipient->responded_at?->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                        <p class="mt-2 whitespace-pre-line text-slate-700">
                                            {{ $recipient->response_text ?: 'Respuesta sin texto visible.' }}
                                        </p>
                                    </div>
                                @else
                                    <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 p-3 text-slate-500">
                                        Sin respuesta registrada.
                                    </div>
                                @endif

                                @if ($recipient->error_message)
                                    <p class="mt-2 text-[11px] text-red-600">
                                        {{ $recipient->error_message }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-slate-500">
                                No hay destinatarios en esta campaña.
                            </div>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="flex h-full min-h-[24rem] items-center justify-center text-center">
                    <div>
                        <x-ui.icon name="megaphone" class="mx-auto h-10 w-10 text-slate-300" />
                        <p class="mt-3 text-sm font-medium text-slate-900">Selecciona una campaña</p>
                        <p class="mt-1 text-xs text-slate-500">Aqui veras destinatarios, errores y acciones de envio.</p>
                    </div>
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.modal id="whatsapp-marketing-campaign-modal" animation="fade" width="6xl"
        heading="Nueva campaña WhatsApp"
        description="Carga un archivo, selecciona plantilla y enlaza variables antes de enviar."
        x-on:close-whatsapp-marketing-campaign-modal.window="$data.close()">
        <form wire:submit="createCampaign" class="space-y-4">
            <x-ui.fieldset label="Datos de campaña">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.field required>
                        <x-ui.label>Nombre</x-ui.label>
                        <x-ui.input wire:model="name" placeholder="Promo verano" />
                        <x-ui.error name="name" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Plantilla</x-ui.label>
                        <select wire:model.live="templateId"
                            class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/15">
                            <option value="">Selecciona una plantilla</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}">
                                    {{ $template->name }} · {{ $template->meta_template_name }} · {{ $template->language_code }}
                                </option>
                            @endforeach
                        </select>
                        <x-ui.error name="templateId" />
                    </x-ui.field>
                </div>

                <x-ui.field>
                    <x-ui.label>Descripcion</x-ui.label>
                    <x-ui.textarea wire:model="description" rows="2" placeholder="Notas internas de la campaña" />
                    <x-ui.error name="description" />
                </x-ui.field>
            </x-ui.fieldset>

            <x-ui.fieldset label="Archivo de destinatarios">
                <x-ui.field required>
                    <x-ui.label>Excel o CSV</x-ui.label>
                    <input type="file" wire:model.live="recipientsFile" accept=".xlsx,.csv,.txt"
                        class="block w-full rounded-xl border border-slate-200 bg-white p-2 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100">
                    <p class="mt-1 text-xs text-slate-500">Columna A: nombre opcional. Columna B: WhatsApp obligatorio.</p>
                    <x-ui.error name="recipientsFile" />
                </x-ui.field>

                @if ($importErrors)
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        {{ implode(' ', array_slice($importErrors, 0, 3)) }}
                    </div>
                @endif

                @if ($previewRows)
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Fila</th>
                                    @foreach ($importColumns as $column)
                                        <th class="px-3 py-2">{{ $column['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($previewRows as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-slate-500">{{ $row['row_number'] }}</td>
                                        @foreach ($importColumns as $column)
                                            <td class="px-3 py-2">{{ $row['data'][$column['key']] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.fieldset>

            @if ($selectedTemplate)
                <x-ui.fieldset label="Plantilla seleccionada">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-900">{{ $selectedTemplate->name }}</span>
                            <span class="rounded-full bg-slate-900 px-2 py-1 text-xs text-white">{{ $selectedTemplate->meta_template_name }}</span>
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700">{{ $selectedTemplate->language_code }}</span>
                        </div>
                        @if ($selectedTemplate->example_text)
                            <div class="mt-3 whitespace-pre-line rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700">
                                {{ $selectedTemplate->example_text }}
                            </div>
                        @endif
                    </div>

                    @if ($selectedTemplate->header_media_type)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-950">
                                Encabezado requerido: {{ match ($selectedTemplate->header_media_type) {
                                    'image' => 'Imagen',
                                    'video' => 'Video',
                                    'document' => 'Documento PDF',
                                    default => $selectedTemplate->header_media_type,
                                } }}
                            </p>

                            <div class="mt-3 grid gap-3 md:grid-cols-3">
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" wire:model.live="headerMediaSource" value="file">
                                    Subir archivo
                                </label>

                                @if ($selectedTemplate->header_media_type === 'image')
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" wire:model.live="headerMediaSource" value="url">
                                        URL publica
                                    </label>
                                @endif
                            </div>

                            @if ($headerMediaSource === 'file')
                                <div class="mt-3">
                                    <input type="file" wire:model="headerMediaFile"
                                        class="block w-full rounded-xl border border-amber-200 bg-white p-2 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-amber-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-amber-800">
                                    <p class="mt-1 text-xs text-amber-700">
                                        Imagen JPG/PNG/WEBP, video MP4/3GPP/MOV o documento PDF segun plantilla.
                                    </p>
                                    <x-ui.error name="headerMediaFile" />
                                </div>
                            @endif

                            @if ($headerMediaSource === 'url')
                                <div class="mt-3">
                                    <x-ui.input wire:model="headerMediaUrl" placeholder="https://..." />
                                    <x-ui.error name="headerMediaUrl" />
                                </div>
                            @endif

                            <x-ui.error name="headerMediaSource" />
                        </div>
                    @endif
                </x-ui.fieldset>

                <x-ui.fieldset label="Mapeo de variables">
                    <div class="grid gap-4 xl:grid-cols-2">
                        @include('livewire.whatsapp.partials.marketing-variable-mappings', [
                            'title' => 'Variables body',
                            'property' => 'bodyMappings',
                            'mappings' => $bodyMappings,
                        ])

                        @include('livewire.whatsapp.partials.marketing-variable-mappings', [
                            'title' => 'Variables boton',
                            'property' => 'buttonMappings',
                            'mappings' => $buttonMappings,
                        ])
                    </div>
                </x-ui.fieldset>
            @endif

            <div class="flex justify-end gap-3 pt-2">
                <x-ui.button type="button" x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancelar
                </x-ui.button>

                <x-ui.button type="submit" color="teal" icon="check">
                    Crear borrador
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
