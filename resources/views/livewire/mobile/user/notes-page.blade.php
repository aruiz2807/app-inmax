<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Nota medica</x-ui.text>
    </div>

    <x-ui.card size="full" class="mx-auto">
        <x-ui.heading class="flex mb-4" level="h3" size="sm">
            <x-ui.icon name="calendar" class="self-center" />
            <x-ui.text class="text-lg ml-2">{{$appointment->formatted_date}}</x-ui.text>
        </x-ui.heading>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->doctor->user->photo_url" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->doctor->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->doctor->specialty->name}}</x-ui.text>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Servicios</x-ui.text>
        </x-ui.heading>

        <div class="flex flex-col w-full">
        @foreach($services as $service)
            <div class="flex items-center justify-between pb-2">
                <x-ui.text class="text-base pr-1">{{$service->service->name}}</x-ui.text>
                <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>{{$service->covered_text}}</x-ui.badge>
            </div>
        @endforeach
        </div>
    </x-ui.card>

    @if($isDoctor)
    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Síntomas</x-ui.text>
        </x-ui.heading>
        <x-ui.text class="text-base">{{$appointment->note->symptoms}}</x-ui.text>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Hallazgos físicos</x-ui.text>
        </x-ui.heading>
        <x-ui.text class="text-base">{{$appointment->note->findings}}</x-ui.text>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Diagnostico</x-ui.text>
        </x-ui.heading>
        <x-ui.text class="text-base">{{$appointment->note->diagnosis}}</x-ui.text>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Tratamiento / Receta</x-ui.text>
        </x-ui.heading>

        @if(count($appointment->prescriptions) > 0)
            <div class="flex flex-col gap-2">
                @foreach($appointment->prescriptions as $prescription)
                    <div class="bg-gray-50 p-2 rounded-lg shadow-sm border border-gray-100">
                        <x-ui.text class="font-bold text-sm">{{ $prescription->medication->name }} ({{ $prescription->medication->trade_name }})</x-ui.text>
                        <x-ui.text class="text-xs text-gray-600">
                            {{ $prescription->quantity }} {{ $prescription->medication->packaging }} • {{ $prescription->dose }} • {{ $prescription->frequency }} • {{ $prescription->duration }}
                        </x-ui.text>
                    </div>
                @endforeach
            </div>
        @endif

        @if($appointment->note->treatment)
            <div class="@if(count($appointment->prescriptions) > 0) mt-2 pt-2 border-t @endif">
                <x-ui.text class="text-base">{{$appointment->note->treatment}}</x-ui.text>
            </div>
        @endif
    </x-ui.card>
    @endif

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Archivo adjunto</x-ui.text>
        </x-ui.heading>

        @foreach($services as $service)
            @if($service->attachment_name)
            <a href="{{ route('attachment.download', $service->id) }}">
                <x-ui.text class="text-base">{{$service->attachment_name}}</x-ui.text>
            </a>
            @endif
        @endforeach
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Notas y recomendaciones</x-ui.text>
        </x-ui.heading>
        <x-ui.text class="text-base">{{$appointment->note->notes}}</x-ui.text>
    </x-ui.card>
</div>
