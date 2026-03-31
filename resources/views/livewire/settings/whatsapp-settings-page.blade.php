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

                    <x-ui.field required>
                        <x-ui.label>Access Token</x-ui.label>
                        <x-ui.input wire:model="accessToken" type="password" placeholder="{{ $hasStoredAccessToken ? 'Token guardado. Escribe uno nuevo para reemplazar.' : 'Pega aqui el token de Meta' }}" />
                        <x-ui.error name="accessToken" />
                    </x-ui.field>
                </x-ui.fieldset>

                <x-ui.fieldset label="Plantillas y lenguaje" class="mt-4">
                    <x-ui.field required>
                        <x-ui.label>Plantilla activacion PIN</x-ui.label>
                        <x-ui.input wire:model="activationTemplateName" placeholder="activation_pin_template" />
                        <x-ui.error name="activationTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Plantilla restablecimiento PIN</x-ui.label>
                        <x-ui.input wire:model="pinResetTemplateName" placeholder="reset_pin_template" />
                        <x-ui.error name="pinResetTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Plantilla preregistro membresía</x-ui.label>
                        <x-ui.input wire:model="preregistrationTemplateName" placeholder="policy_preregistration_template" />
                        <x-ui.error name="preregistrationTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Plantilla solicitud de cita</x-ui.label>
                        <x-ui.input wire:model="appointmentRequestTemplateName" placeholder="appointment_request_template" />
                        <x-ui.error name="appointmentRequestTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Plantilla finalizacion cita</x-ui.label>
                        <x-ui.input wire:model="appointmentCompletedTemplateName" placeholder="appointment_completed_template" />
                        <x-ui.error name="appointmentCompletedTemplateName" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Idioma por defecto</x-ui.label>
                        <x-ui.input wire:model="defaultLanguage" placeholder="es_MX" />
                        <x-ui.error name="defaultLanguage" />
                    </x-ui.field>
                </x-ui.fieldset>

                <x-ui.fieldset label="Parametros por plantilla" class="mt-4">
                    <div class="space-y-5">
                        @foreach ($templateSections as $section)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="pb-3">
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $section['title'] }}</h4>
                                    <p class="text-xs text-slate-500">Define el orden exacto de variables que espera la plantilla.</p>
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
                Realiza una prueba manual de envio de plantilla con telefono, nombre de plantilla, idioma, parametros de body y parametros de boton URL.
            </p>

            <form wire:submit="sendTestMessage" class="pt-4">
                <x-ui.fieldset label="Datos de prueba">
                    <x-ui.field required>
                        <x-ui.label>Telefono destino</x-ui.label>
                        <x-ui.input wire:model="testPhone" placeholder="5213312345678" />
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
                        <x-ui.textarea wire:model="testParameters" placeholder="Juan Perez&#10;1234" />
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
</div>
