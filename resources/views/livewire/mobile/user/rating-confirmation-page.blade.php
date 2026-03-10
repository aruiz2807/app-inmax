<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <a href="{{ route('user.home') }}">
            <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" />
        </a>
        <x-ui.text class="text-2xl">Calificar servicio</x-ui.text>
    </div>

    <x-ui.card size="full" class="mx-auto">
        <x-ui.heading class="flex" level="h3" size="sm">
            <x-ui.icon name="check" class="self-center" />
            <x-ui.text class="text-lg ml-2">Servicio calificado</x-ui.text>
        </x-ui.heading>

        <x-ui.text class="text-lg ml-2">Sus comentarios fueron recibidos, gracias.</x-ui.text>
    </x-ui.card>
</div>
