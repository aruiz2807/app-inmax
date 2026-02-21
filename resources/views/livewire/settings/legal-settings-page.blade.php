<div>
    <x-slot name="header">
        {{ __('Legal Settings') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Terminos y condiciones y aviso de privacidad
            </x-ui.heading>
            <p class="mt-2">
                Configura versiones legales con historial y deja una sola version activa por tipo. Estos textos se mostraran y se aceptaran al confirmar PIN.
            </p>
        </x-ui.card>
    </div>

    <div class="pt-2 grid gap-2 lg:grid-cols-2">
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Nueva version TyC
            </x-ui.heading>

            <form wire:submit="saveTerms" class="pt-4">
                <x-ui.fieldset label="Terminos y condiciones">
                    <x-ui.field required>
                        <x-ui.label>Version</x-ui.label>
                        <x-ui.input wire:model="termsVersion" placeholder="v1.0" />
                        <x-ui.error name="termsVersion" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Titulo</x-ui.label>
                        <x-ui.input wire:model="termsTitle" placeholder="Terminos y condiciones Inmax" />
                        <x-ui.error name="termsTitle" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Contenido</x-ui.label>
                        <x-ui.textarea wire:model="termsContent" rows="8" placeholder="Escribe aqui el texto legal de terminos y condiciones..." />
                        <x-ui.error name="termsContent" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Vigencia desde</x-ui.label>
                        <x-ui.input wire:model="termsEffectiveAt" type="datetime-local" />
                        <x-ui.error name="termsEffectiveAt" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Vence en</x-ui.label>
                        <x-ui.input wire:model="termsExpiresAt" type="datetime-local" />
                        <x-ui.error name="termsExpiresAt" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-checkbox id="terms_activate" wire:model="termsActivate">
                            Activar esta version al guardar
                        </x-checkbox>
                    </x-ui.field>
                </x-ui.fieldset>

                <div class="w-full flex justify-end gap-3 pt-4">
                    <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                        Guardar TyC
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Nueva version Aviso
            </x-ui.heading>

            <form wire:submit="savePrivacy" class="pt-4">
                <x-ui.fieldset label="Aviso de privacidad">
                    <x-ui.field required>
                        <x-ui.label>Version</x-ui.label>
                        <x-ui.input wire:model="privacyVersion" placeholder="v1.0" />
                        <x-ui.error name="privacyVersion" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Titulo</x-ui.label>
                        <x-ui.input wire:model="privacyTitle" placeholder="Aviso de privacidad Inmax" />
                        <x-ui.error name="privacyTitle" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Contenido</x-ui.label>
                        <x-ui.textarea wire:model="privacyContent" rows="8" placeholder="Escribe aqui el texto legal de aviso de privacidad..." />
                        <x-ui.error name="privacyContent" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Vigencia desde</x-ui.label>
                        <x-ui.input wire:model="privacyEffectiveAt" type="datetime-local" />
                        <x-ui.error name="privacyEffectiveAt" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Vence en</x-ui.label>
                        <x-ui.input wire:model="privacyExpiresAt" type="datetime-local" />
                        <x-ui.error name="privacyExpiresAt" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-checkbox id="privacy_activate" wire:model="privacyActivate">
                            Activar esta version al guardar
                        </x-checkbox>
                    </x-ui.field>
                </x-ui.fieldset>

                <div class="w-full flex justify-end gap-3 pt-4">
                    <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                        Guardar Aviso
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    <div class="pt-2 grid gap-2 lg:grid-cols-2">
        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Historial TyC
            </x-ui.heading>

            <div class="pt-3 space-y-3">
                @forelse ($termsDocuments as $document)
                    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold">{{ $document->version }} - {{ $document->title }}</p>
                            @if ($document->is_active)
                                <span class="text-xs font-semibold rounded-md px-2 py-1 bg-teal-100 text-teal-800">
                                    Activa
                                </span>
                            @else
                                <span class="text-xs font-semibold rounded-md px-2 py-1 bg-neutral-100 text-neutral-700">
                                    Inactiva
                                </span>
                            @endif
                        </div>

                        <p class="text-xs mt-1">
                            Creada: {{ $document->created_at?->format('d/m/Y H:i') }} |
                            Vigencia: {{ $document->effective_at?->format('d/m/Y H:i') ?? 'Inmediata' }} |
                            Vence: {{ $document->expires_at?->format('d/m/Y H:i') ?? 'Sin vencimiento' }}
                        </p>

                        <p class="text-sm mt-2 text-neutral-700 dark:text-neutral-300">
                            {{ \Illuminate\Support\Str::limit($document->content, 220) }}
                        </p>

                        @if (! $document->is_active)
                            <div class="pt-3 flex justify-end">
                                <x-ui.button type="button" icon="check-circle" color="teal" wire:click="activateDocument({{ $document->id }})">
                                    Activar version
                                </x-ui.button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-neutral-600 dark:text-neutral-300">
                        Aun no hay versiones registradas.
                    </p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card size="full">
            <x-ui.heading level="h3" size="sm">
                Historial Aviso
            </x-ui.heading>

            <div class="pt-3 space-y-3">
                @forelse ($privacyDocuments as $document)
                    <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold">{{ $document->version }} - {{ $document->title }}</p>
                            @if ($document->is_active)
                                <span class="text-xs font-semibold rounded-md px-2 py-1 bg-teal-100 text-teal-800">
                                    Activa
                                </span>
                            @else
                                <span class="text-xs font-semibold rounded-md px-2 py-1 bg-neutral-100 text-neutral-700">
                                    Inactiva
                                </span>
                            @endif
                        </div>

                        <p class="text-xs mt-1">
                            Creada: {{ $document->created_at?->format('d/m/Y H:i') }} |
                            Vigencia: {{ $document->effective_at?->format('d/m/Y H:i') ?? 'Inmediata' }} |
                            Vence: {{ $document->expires_at?->format('d/m/Y H:i') ?? 'Sin vencimiento' }}
                        </p>

                        <p class="text-sm mt-2 text-neutral-700 dark:text-neutral-300">
                            {{ \Illuminate\Support\Str::limit($document->content, 220) }}
                        </p>

                        @if (! $document->is_active)
                            <div class="pt-3 flex justify-end">
                                <x-ui.button type="button" icon="check-circle" color="teal" wire:click="activateDocument({{ $document->id }})">
                                    Activar version
                                </x-ui.button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-neutral-600 dark:text-neutral-300">
                        Aun no hay versiones registradas.
                    </p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
