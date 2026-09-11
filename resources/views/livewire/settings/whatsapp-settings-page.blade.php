<div>
    <x-slot name="header">
        {{ __('WhatsApp Settings') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Configuracion Meta WhatsApp Cloud API
            </x-ui.heading>
            <p class="mt-2">
                Configura la version del API, el ID de linea, token y las plantillas que se usaran para PIN, preregistros y notificaciones operativas.
            </p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <form wire:submit="saveSettings">
                <x-ui.fieldset label="Credenciales API">
                    <x-ui.field required>
                        <x-ui.label>Version</x-ui.label>
                        <x-ui.input wire:model="apiVersion" placeholder="v22.0" />
                        <x-ui.error name="apiVersion" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>ID Linea Meta (Phone Number ID)</x-ui.label>
                        <x-ui.input wire:model="phoneNumberId" placeholder="113206948334320" />
                        <x-ui.error name="phoneNumberId" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Business Account ID</x-ui.label>
                        <x-ui.input wire:model="businessAccountId" placeholder="ID de WhatsApp Business Account" />
                        <p class="mt-1 text-xs text-slate-500">Requerido para sincronizar y crear plantillas desde Meta.</p>
                        <x-ui.error name="businessAccountId" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Meta App ID</x-ui.label>
                        <x-ui.input wire:model="metaAppId" placeholder="ID de la app de Meta" />
                        <p class="mt-1 text-xs text-slate-500">Requerido para crear plantillas con encabezado multimedia.</p>
                        <x-ui.error name="metaAppId" />
                    </x-ui.field>

                    <x-ui.field :required="! $hasStoredAccessToken">
                        <x-ui.label>Access Token</x-ui.label>
                        <x-ui.input wire:model="accessToken" type="password" placeholder="{{ $hasStoredAccessToken ? 'Token guardado. Escribe uno nuevo para reemplazar.' : 'Pega aqui el token de Meta' }}" />
                        <p class="mt-1 text-xs text-slate-500">Si ya existe uno guardado, deja el campo vacío para conservarlo.</p>
                        <x-ui.error name="accessToken" />
                    </x-ui.field>
                </x-ui.fieldset>

                <x-ui.fieldset label="Webhook Meta" class="mt-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.field>
                            <x-ui.label>URL del webhook</x-ui.label>
                            <x-ui.input value="{{ route('webhooks.whatsapp.verify') }}" readonly />
                            <p class="mt-1 text-xs text-slate-500">Usa esta misma URL en Meta para verificacion y eventos.</p>
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Webhook activo</x-ui.label>
                            <div class="pt-2">
                                <x-ui.switch
                                    wire:model.live="webhookEnabled"
                                    :checked="$webhookEnabled"
                                    color="teal"
                                    label="Recibir eventos de mensajes y estatus"
                                />
                            </div>
                            <x-ui.error name="webhookEnabled" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Verify Token</x-ui.label>
                            <x-ui.input
                                wire:model="webhookVerifyToken"
                                placeholder="{{ $hasStoredWebhookVerifyToken ? 'Token guardado. Escribe uno nuevo para reemplazar.' : 'token-de-verificacion-meta' }}"
                            />
                            <p class="mt-1 text-xs text-slate-500">Si ya existe uno guardado, deja el campo vacío para conservarlo.</p>
                            <x-ui.error name="webhookVerifyToken" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>App Secret</x-ui.label>
                            <x-ui.input wire:model="appSecret" type="password" placeholder="{{ $hasStoredAppSecret ? 'Secret guardado. Escribe uno nuevo para reemplazar.' : 'Pega aqui el app secret de Meta' }}" />
                            <p class="mt-1 text-xs text-slate-500">Es opcional para guardar la configuración. Si dejas el campo vacío, se mantiene el valor guardado.</p>
                            <p class="mt-1 text-xs text-slate-500">Actualmente no bloquea los webhooks entrantes. Solo sirve si después quieres validar la firma de Meta.</p>
                            <x-ui.error name="appSecret" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Ultimo evento recibido</x-ui.label>
                            <x-ui.input value="{{ $webhookLastReceivedAt ?: 'Sin eventos aun' }}" readonly />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Ultimo estado webhook</x-ui.label>
                            <x-ui.input value="{{ $webhookLastStatus ?: 'Sin estado' }}" readonly />
                        </x-ui.field>
                    </div>
                </x-ui.fieldset>

                <x-ui.fieldset label="Idioma global" class="mt-4">
                    <x-ui.field required>
                        <x-ui.label>Idioma por defecto fallback</x-ui.label>
                        <x-ui.input wire:model="defaultLanguage" placeholder="es_MX" />
                        <p class="mt-1 text-xs text-slate-500">Se usa solo si una plantilla no tiene idioma especifico.</p>
                        <x-ui.error name="defaultLanguage" />
                    </x-ui.field>
                </x-ui.fieldset>

                <x-ui.fieldset label="Plantillas Meta" class="mt-4">
                    <div class="pb-4">
                        <div class="min-w-0">
                            <p class="text-sm text-slate-600">
                                Sincroniza plantillas existentes en Meta o crea nuevas plantillas para revision.
                            </p>
                        </div>
                    </div>

                    <div class="w-full min-w-0 overflow-hidden rounded-xl border border-slate-200">
                        <div class="w-full min-w-0">
                            <div class="grid gap-3 border-b border-slate-200 bg-white px-4 py-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                <div class="min-w-0">
                                    <x-ui.input
                                        wire:model.live.debounce.350ms="metaTemplateSearch"
                                        placeholder="Buscar por plantilla, idioma, estado o categoria..."
                                    />
                                </div>

                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-ui.button type="button" size="sm" variant="outline" icon="arrow-path" wire:click="syncMetaTemplates">
                                        Sincronizar Meta
                                    </x-ui.button>

                                    <x-ui.modal.trigger id="whatsapp-meta-template-modal">
                                        <x-ui.button type="button" size="sm" color="teal" icon="plus-circle">
                                            Nueva plantilla Meta
                                        </x-ui.button>
                                    </x-ui.modal.trigger>
                                </div>
                            </div>

                            <div class="grid grid-cols-[minmax(11rem,1.25fr)_4.5rem_7rem_7rem_minmax(7rem,.8fr)_5rem_12rem] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <span>Plantilla</span>
                                <span>Idioma</span>
                                <span>Estado</span>
                                <span>Categoria</span>
                                <span>Variables</span>
                                <span>Activa</span>
                                <span class="text-right">Acciones</span>
                            </div>

                            <div class="divide-y divide-slate-200">
                                @forelse ($metaTemplates as $item)
                                    @php
                                        $template = $item['model'];
                                        $requirements = $item['requirements'];
                                    @endphp
                                    <div class="grid grid-cols-[minmax(11rem,1.25fr)_4.5rem_7rem_7rem_minmax(7rem,.8fr)_5rem_12rem] items-center gap-3 px-4 py-4 text-sm"
                                        wire:key="meta-template-{{ $template->id }}">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">{{ $template->name }}</p>
                                            <p class="truncate text-xs text-slate-500">{{ $template->meta_id }}</p>
                                        </div>

                                        <div class="text-slate-600">{{ $template->language_code }}</div>

                                        <div>
                                            <x-ui.badge :color="$template->status === 'APPROVED' ? 'emerald' : ($template->status === 'REJECTED' ? 'red' : 'amber')" size="sm" pill>
                                                {{ $template->status }}
                                            </x-ui.badge>
                                        </div>

                                        <div class="text-slate-600">{{ $template->category ?: 'N/A' }}</div>

                                        <div class="text-xs text-slate-600">
                                            {{ $requirements['body_variables'] }} body /
                                            {{ $requirements['header_variables'] }} header /
                                            {{ $item['button_variables'] }} boton
                                            @if ($item['header_media_type'])
                                                <p class="mt-1 text-slate-500">Header {{ $item['header_media_type'] }}</p>
                                            @endif
                                        </div>

                                        <div>
                                            <x-ui.badge :color="$template->is_active ? 'emerald' : 'slate'" size="sm" pill>
                                                {{ $template->is_active ? 'Si' : 'No' }}
                                            </x-ui.badge>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <x-ui.button type="button" size="sm" variant="outline" icon="eye"
                                                wire:click="previewMetaTemplate({{ $template->id }})">
                                                Ver
                                            </x-ui.button>

                                            <x-ui.button type="button" size="sm" variant="outline"
                                                wire:click="toggleMetaTemplate({{ $template->id }})">
                                                {{ $template->is_active ? 'Ocultar' : 'Activar' }}
                                            </x-ui.button>

                                            <x-ui.button type="button" size="sm" color="teal"
                                                wire:click="createOperationalTemplateFromMeta({{ $template->id }})"
                                                :disabled="$template->status !== 'APPROVED'">
                                                Usar
                                            </x-ui.button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-10 text-center text-sm text-slate-500">
                                        No hay plantillas sincronizadas desde Meta.
                                    </div>
                                @endforelse
                            </div>

                            @if ($metaTemplates->hasPages())
                                <div class="border-t border-slate-200 bg-white px-4 py-3">
                                    {{ $metaTemplates->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </x-ui.fieldset>

                <x-ui.fieldset label="Parametros por plantilla" class="mt-4">
                    <div class="space-y-5">
                        @foreach ($templateSections as $section)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="pb-3">
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $section['title'] }}</h4>
                                    <p class="text-xs text-slate-500">Define el orden exacto de variables que espera la plantilla.</p>
                                </div>

                                <div class="grid gap-4 pb-4 md:grid-cols-2">
                                    <x-ui.field required>
                                        <x-ui.label>Nombre de plantilla</x-ui.label>
                                        <x-ui.input
                                            wire:model="{{ $section['template_field'] }}"
                                            placeholder="template_name"
                                        />
                                        <x-ui.error name="{{ $section['template_field'] }}" />
                                    </x-ui.field>

                                    <x-ui.field required>
                                        <x-ui.label>Idioma</x-ui.label>
                                        <x-ui.input
                                            wire:model="{{ $section['language_field'] }}"
                                            placeholder="es_MX"
                                        />
                                        <x-ui.error name="{{ $section['language_field'] }}" />
                                    </x-ui.field>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    @foreach ([
                                        [
                                            'label' => $section['body_label'],
                                            'field' => $section['body_field'],
                                            'scope' => $section['body_scope'],
                                        ],
                                        [
                                            'label' => $section['button_label'],
                                            'field' => $section['button_field'],
                                            'scope' => $section['button_scope'],
                                        ],
                                    ] as $mapping)
                                        @php
                                            $values = data_get($this, $mapping['field'], []);
                                            $options = $parameterOptions[$mapping['scope']] ?? [];
                                        @endphp

                                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-medium text-slate-900">{{ $section['title'] }} - {{ $mapping['label'] }}</p>
                                                    <p class="text-xs text-slate-500">
                                                        @if ($options === [])
                                                            Sin valores enlazables para esta seccion.
                                                        @else
                                                            Selecciona el valor del sistema para cada variable `{{ '{' }}`{{ '{' }}n{{ '}' }}{{ '}' }}`.
                                                        @endif
                                                    </p>
                                                </div>

                                                @if ($options !== [])
                                                    <x-ui.button
                                                        type="button"
                                                        size="sm"
                                                        icon="plus-circle"
                                                        variant="outline"
                                                        color="teal"
                                                        wire:click="addTemplateParameter('{{ $mapping['field'] }}')"
                                                    >
                                                        Agregar parametro
                                                    </x-ui.button>
                                                @endif
                                            </div>

                                            <div class="space-y-3 pt-3">
                                                @forelse ($values as $index => $value)
                                                    <div class="flex items-end gap-2" wire:key="{{ $mapping['field'] }}-{{ $index }}">
                                                        <div class="flex-1">
                                                            <x-ui.label>Variable {{ $index + 1 }}</x-ui.label>
                                                            <x-ui.select wire:model.live="{{ $mapping['field'] }}.{{ $index }}" placeholder="Selecciona un valor">
                                                                @foreach ($options as $optionValue => $optionLabel)
                                                                    <x-ui.select.option value="{{ $optionValue }}">
                                                                        {{ $optionLabel }} ({{ $optionValue }})
                                                                    </x-ui.select.option>
                                                                @endforeach
                                                            </x-ui.select>
                                                            <x-ui.error name="{{ $mapping['field'] }}" />
                                                        </div>

                                                        <x-ui.button
                                                            type="button"
                                                            size="sm"
                                                            icon="trash"
                                                            variant="outline"
                                                            color="red"
                                                            wire:click="removeTemplateParameter('{{ $mapping['field'] }}', {{ $index }})"
                                                        />
                                                    </div>
                                                @empty
                                                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                                                        No hay parametros configurados para esta seccion.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.fieldset>

                <div class="w-full flex justify-end gap-3 pt-4">
                    <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                        Guardar configuracion
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Envio de prueba
            </x-ui.heading>
            <p class="mt-2">
                Realiza una prueba manual de envio de plantilla con teléfono, nombre de plantilla, idioma, parametros de body y parametros de boton URL.
            </p>

            <form wire:submit="sendTestMessage" class="pt-4">
                <x-ui.fieldset label="Datos de prueba">
                    <x-ui.field required>
                        <x-ui.label>Teléfono destino</x-ui.label>
                        <x-ui.input wire:model="testPhone" placeholder="3300000000" />
                        <x-ui.error name="testPhone" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Nombre de plantilla</x-ui.label>
                        <x-ui.input wire:model="testTemplateName" placeholder="activation_pin_template" />
                        <x-ui.error name="testTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Idioma</x-ui.label>
                        <x-ui.input wire:model="testLanguageCode" placeholder="es_MX" />
                        <x-ui.error name="testLanguageCode" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Parametros body (uno por linea)</x-ui.label>
                        <x-ui.textarea wire:model="testParameters" placeholder="Nombre Apellido" />
                        <x-ui.error name="testParameters" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Parametros boton URL (uno por linea)</x-ui.label>
                        <x-ui.textarea wire:model="testButtonUrlParameters" placeholder="abc123token" />
                        <x-ui.error name="testButtonUrlParameters" />
                    </x-ui.field>
                </x-ui.fieldset>

                <div class="w-full flex justify-end gap-3 pt-4">
                    <x-ui.button type="submit" icon="paper-airplane" variant="primary" color="teal">
                        Enviar prueba
                    </x-ui.button>
                </div>
            </form>

            @if ($lastTestMessageId || $lastTestResponse)
                <div class="pt-4">
                    <x-ui.fieldset label="Ultima respuesta API">
                        @if ($lastTestMessageId)
                            <p class="text-sm pb-2">
                                <span class="font-semibold">Message ID:</span> {{ $lastTestMessageId }}
                            </p>
                        @endif

                        @if ($lastTestResponse)
                            <pre class="text-xs bg-neutral-900 text-neutral-100 rounded-lg p-3 overflow-auto">{{ $lastTestResponse }}</pre>
                        @endif
                    </x-ui.fieldset>
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.modal id="whatsapp-meta-template-modal" animation="fade" width="4xl"
        heading="Nueva plantilla Meta"
        description="Crea una plantilla en Meta para revision. Cuando sea aprobada, sincronizala y marcala como usable."
        x-on:close-whatsapp-meta-template-modal.window="$data.close()">
        <form wire:submit="createMetaTemplate" class="space-y-4">
            <x-ui.fieldset label="Datos generales">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.field required>
                        <x-ui.label>Nombre Meta</x-ui.label>
                        <x-ui.input wire:model="newTemplateName" placeholder="promo_cliente_2026" />
                        <p class="mt-1 text-xs text-slate-500">Solo minusculas, numeros y guion bajo.</p>
                        <x-ui.error name="newTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Idioma</x-ui.label>
                        <x-ui.input wire:model="newTemplateLanguage" placeholder="es_MX" />
                        <x-ui.error name="newTemplateLanguage" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Categoria</x-ui.label>
                        <select wire:model="newTemplateCategory"
                            class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/15">
                            <option value="MARKETING">MARKETING</option>
                            <option value="UTILITY">UTILITY</option>
                        </select>
                        <x-ui.error name="newTemplateCategory" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Tipo de encabezado</x-ui.label>
                        <select wire:model.live="newTemplateHeaderType"
                            class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/15">
                            <option value="NONE">Sin encabezado</option>
                            <option value="TEXT">Texto</option>
                            <option value="IMAGE">Imagen</option>
                            <option value="VIDEO">Video</option>
                            <option value="DOCUMENT">Documento PDF</option>
                        </select>
                        <x-ui.error name="newTemplateHeaderType" />
                    </x-ui.field>
                </div>
            </x-ui.fieldset>

            @if ($newTemplateHeaderType === 'TEXT')
                <x-ui.fieldset label="Encabezado de texto">
                    <x-ui.field required>
                        <x-ui.label>Texto encabezado</x-ui.label>
                        <x-ui.input wire:model="newTemplateHeaderText" placeholder="Hola {{1}}" />
                        <x-ui.error name="newTemplateHeaderText" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Ejemplos de encabezado</x-ui.label>
                        <x-ui.input wire:model="newTemplateHeaderExamples" placeholder="Juan" />
                        <p class="mt-1 text-xs text-slate-500">Un ejemplo por variable, separado con |.</p>
                        <x-ui.error name="newTemplateHeaderExamples" />
                    </x-ui.field>
                </x-ui.fieldset>
            @elseif (in_array($newTemplateHeaderType, ['IMAGE', 'VIDEO', 'DOCUMENT'], true))
                <x-ui.fieldset label="Muestra multimedia">
                    <x-ui.field required>
                        <x-ui.label>Archivo de muestra</x-ui.label>
                        <input type="file" wire:model="newTemplateHeaderSample"
                            class="block w-full rounded-xl border border-slate-200 bg-white p-2 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100">
                        <p class="mt-1 text-xs text-slate-500">
                            Imagen: JPG/PNG. Video: MP4/3GPP/MOV. Documento: PDF.
                        </p>
                        <x-ui.error name="newTemplateHeaderSample" />
                    </x-ui.field>
                </x-ui.fieldset>
            @endif

            <x-ui.fieldset label="Contenido">
                <x-ui.field required>
                    <x-ui.label>Body</x-ui.label>
                    <x-ui.textarea wire:model="newTemplateBody" rows="5"
                        placeholder="Hola {{1}}, tenemos una promocion para ti." />
                    <p class="mt-1 text-xs text-slate-500">Variables consecutivas: @{{1}}, @{{2}}.</p>
                    <x-ui.error name="newTemplateBody" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Ejemplos body</x-ui.label>
                    <x-ui.input wire:model="newTemplateBodyExamples" placeholder="Juan|Plan familiar" />
                    <p class="mt-1 text-xs text-slate-500">Un ejemplo por variable, separado con |.</p>
                    <x-ui.error name="newTemplateBodyExamples" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Footer</x-ui.label>
                    <x-ui.input wire:model="newTemplateFooter" placeholder="SANEMI SOLUCIONES" />
                    <x-ui.error name="newTemplateFooter" />
                </x-ui.field>
            </x-ui.fieldset>

            <div class="flex justify-end gap-3 pt-2">
                <x-ui.button type="button" x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancelar
                </x-ui.button>

                <x-ui.button type="submit" color="teal" icon="paper-airplane">
                    Enviar a Meta
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    @php
        $preview = $this->metaTemplatePreviewData();
        $previewTemplate = $preview['template'];
        $previewHeader = $preview['header'];
        $previewBody = $preview['body'];
        $previewFooter = $preview['footer'];
        $previewButtons = $preview['buttons'];
    @endphp

    <x-ui.modal id="whatsapp-meta-template-preview-modal" animation="fade" width="4xl"
        heading="Vista de plantilla Meta"
        description="Contenido sincronizado desde Meta, incluyendo body, ejemplos y componentes."
        x-on:open-whatsapp-meta-template-preview-modal.window="$data.open()">
        @if ($previewTemplate)
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-slate-900">{{ $previewTemplate->name }}</span>
                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700">{{ $previewTemplate->language_code }}</span>
                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-700">{{ $previewTemplate->status }}</span>
                        @if ($previewTemplate->category)
                            <span class="rounded-full bg-slate-200 px-2 py-1 text-xs text-slate-700">{{ $previewTemplate->category }}</span>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Meta ID: {{ $previewTemplate->meta_id }}</p>
                </div>

                @if ($previewHeader)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-semibold text-slate-900">Encabezado</p>
                        <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">
                            {{ $previewHeader['format'] ?? 'TEXT' }}
                        </p>

                        @if (filled($previewHeader['text'] ?? null))
                            <div class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                {{ $previewHeader['text'] }}
                            </div>
                        @endif

                        @if (data_get($previewHeader, 'example.header_text'))
                            <div class="mt-3">
                                <p class="text-xs font-semibold text-slate-500">Ejemplos header</p>
                                <p class="mt-1 text-sm text-slate-700">
                                    {{ implode(' | ', (array) data_get($previewHeader, 'example.header_text', [])) }}
                                </p>
                            </div>
                        @elseif (data_get($previewHeader, 'example.header_handle'))
                            <div class="mt-3">
                                <p class="text-xs font-semibold text-slate-500">Muestra multimedia</p>
                                <p class="mt-1 break-all text-sm text-slate-700">
                                    {{ implode(' | ', (array) data_get($previewHeader, 'example.header_handle', [])) }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-900">Body</p>
                    <div class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                        {{ $previewBody['text'] ?? 'Sin body sincronizado.' }}
                    </div>

                    @if (data_get($previewBody, 'example.body_text.0'))
                        <div class="mt-3">
                            <p class="text-xs font-semibold text-slate-500">Ejemplos body</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ((array) data_get($previewBody, 'example.body_text.0', []) as $example)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                                        {{ $example }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @if ($previewFooter)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-semibold text-slate-900">Footer</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $previewFooter['text'] ?? '' }}</p>
                    </div>
                @endif

                @if ($previewButtons)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-sm font-semibold text-slate-900">Botones</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($previewButtons as $button)
                                <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                    <p class="font-medium">{{ $button['text'] ?? 'Boton' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $button['type'] ?? '' }}</p>
                                    @if (filled($button['url'] ?? null))
                                        <p class="mt-1 break-all text-xs text-slate-500">{{ $button['url'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                Selecciona una plantilla para ver su contenido.
            </div>
        @endif
    </x-ui.modal>
</div>
