<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Nota medica</x-ui.text>
    </div>

    <x-ui.card size="full" class="mx-auto">
        <x-ui.heading class="flex" level="h3" size="sm">
            <x-ui.icon name="calendar" class="self-center" />
            <x-ui.text class="text-lg ml-2">Consulta</x-ui.text>
        </x-ui.heading>

        <div class="grid grid-cols-[auto_6rem] justify-stretch items-center pt-2">
            <x-ui.text class="text-base">{{$appointment->formatted_date}}</x-ui.text>
            <x-ui.badge :icon="$appointment->covered_icon" variant="outline" :color="$appointment->covered_color" pill>
                {{$appointment->covered_text}}
            </x-ui.badge>
        </div>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" src="/img/user.png" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->user->policy->number}}</x-ui.text>
            </div>
        </div>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" src="/img/doctor.png" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->doctor->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->doctor->specialty->name}}</x-ui.text>
            </div>
        </div>
    </x-ui.card>

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
            <x-ui.text class="text-base ml-2">Tratamiento</x-ui.text>
        </x-ui.heading>
        <x-ui.text class="text-base">{{$appointment->note->treatment}}</x-ui.text>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Archivo adjunto</x-ui.text>
        </x-ui.heading>
        @if($appointment->note->attachment_name)
        <a href="{{ route('attachment.download', $appointment->note->id) }}">
            <x-ui.text class="text-base">{{$appointment->note->attachment_name}}</x-ui.text>
        </a>
        @endif
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Notas y recomendaciones</x-ui.text>
        </x-ui.heading>
        <x-ui.text class="text-base">{{$appointment->note->notes}}</x-ui.text>
    </x-ui.card>
</div>
