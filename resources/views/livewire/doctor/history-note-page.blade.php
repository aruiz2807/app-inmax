<div class="space-y-4">
    <x-slot name="header">
        Nota medica
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="mb-4 flex" level="h3" size="sm">
            <x-ui.icon name="calendar" class="self-center" />
            <x-ui.text class="ml-2 text-lg">{{ $appointment->formatted_date }}</x-ui.text>
        </x-ui.heading>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex mt-1">
                <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->user->photo_url" circle />
                <div class="pl-4">
                    <x-ui.text class="pt-1 text-lg">{{ $appointment->user->name }}</x-ui.text>
                    <x-ui.text class="text-sm opacity-75">{{ $appointment->user->policy->number }}</x-ui.text>
                </div>
            </div>

            <div class="flex mt-1">
                <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->doctor->user->photo_url" circle />
                <div class="pl-4">
                    <x-ui.text class="pt-1 text-lg">{{ $appointment->doctor->user->name }}</x-ui.text>
                    <x-ui.text class="text-sm opacity-75">{{ $appointment->doctor->specialty?->name }}</x-ui.text>
                </div>

                <div class="pl-4 ml-auto flex items-right">
                    <x-ui.button href="{{ route('doctor.record', ['user' => $appointment->user->id]) }}" color="teal" icon="clipboard-document-list" class="w-full mt-2">
                        Historial del paciente
                    </x-ui.button>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="space-y-4">
            <x-ui.card size="full">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="clipboard-document-list" class="self-center" />
                    <x-ui.text class="ml-2 text-base">Servicios</x-ui.text>
                </x-ui.heading>

                <div class="grid grid-cols-1 gap-2 lg:grid-cols-1">
                    @foreach($services as $service)
                        <div class="flex items-center justify-between rounded-lg border border-neutral-200 bg-white p-3">
                            <x-ui.text class="pr-2 text-base">{{ $service->name }}</x-ui.text>
                            <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>
                                {{ $service->covered_text }}
                            </x-ui.badge>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            @if($isDoctor)
                <x-ui.card size="full">
                    <x-ui.heading class="flex pb-2" level="h3" size="sm">
                        <x-ui.icon name="clipboard-document-list" class="self-center" />
                        <x-ui.text class="ml-2 text-base">Sintomas</x-ui.text>
                    </x-ui.heading>
                    <x-ui.text class="text-base">{{ $appointment->note->symptoms }}</x-ui.text>
                </x-ui.card>

                <x-ui.card size="full">
                    <x-ui.heading class="flex pb-2" level="h3" size="sm">
                        <x-ui.icon name="clipboard-document-list" class="self-center" />
                        <x-ui.text class="ml-2 text-base">Hallazgos fisicos</x-ui.text>
                    </x-ui.heading>
                    <x-ui.text class="text-base">{{ $appointment->note->findings }}</x-ui.text>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-4">
            @if($isDoctor)
                <x-ui.card size="full">
                    <x-ui.heading class="flex pb-2" level="h3" size="sm">
                        <x-ui.icon name="clipboard-document-list" class="self-center" />
                        <x-ui.text class="ml-2 text-base">Diagnostico</x-ui.text>
                    </x-ui.heading>
                    <x-ui.text class="text-base">{{ $appointment->note->diagnosis }}</x-ui.text>
                </x-ui.card>

                <x-ui.card size="full">
                    <x-ui.heading class="flex pb-2" level="h3" size="sm">
                        <x-ui.icon name="clipboard-document-list" class="self-center" />
                        <x-ui.text class="ml-2 text-base">Tratamiento / Receta</x-ui.text>
                    </x-ui.heading>

                    @if(count($appointment->prescriptions) > 0)
                        <div class="grid grid-cols-1 gap-2 lg:grid-cols-1">
                            @foreach($appointment->prescriptions as $prescription)
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-2 shadow-sm">
                                    <x-ui.text class="text-sm font-bold">{{ $prescription->medication->name }} ({{ $prescription->medication->trade_name }})</x-ui.text>
                                    <x-ui.text class="text-xs text-gray-600">
                                        {{ $prescription->quantity }} {{ $prescription->medication->packaging }} • {{ $prescription->dose }} • {{ $prescription->frequency }} • {{ $prescription->duration }}
                                    </x-ui.text>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($appointment->note->treatment)
                        <div class="@if(count($appointment->prescriptions) > 0) mt-2 border-t pt-2 @endif">
                            <x-ui.text class="text-base">{{ $appointment->note->treatment }}</x-ui.text>
                        </div>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.card size="full">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="clipboard-document-list" class="self-center" />
                    <x-ui.text class="ml-2 text-base">Notas y recomendaciones</x-ui.text>
                </x-ui.heading>
                <x-ui.text class="text-base">{{ $appointment->note->notes }}</x-ui.text>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card size="full" class="xl:sticky xl:top-6">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="paper-clip" class="self-center" />
                    <x-ui.text class="ml-2 text-base">Archivos adjuntos</x-ui.text>
                </x-ui.heading>

                <div class="space-y-2">
                    @php
                        $hasAttachments = false;
                    @endphp

                    @foreach($services as $service)
                        @if($service->attachment_name)
                            @php $hasAttachments = true; @endphp
                            <a
                                href="{{ route('attachment.download', $service->id) }}"
                                class="block rounded-md border border-neutral-200 px-3 py-2 text-sm text-sky-700 hover:bg-sky-50 transition-colors"
                            >
                                {{ $service->attachment_name }}
                            </a>
                        @endif
                    @endforeach

                    @if(! $hasAttachments)
                        <x-ui.text class="text-sm opacity-70">No hay archivos adjuntos.</x-ui.text>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
