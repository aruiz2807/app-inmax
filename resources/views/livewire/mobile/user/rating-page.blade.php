<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Calificar servicio</x-ui.text>
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
            <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->user->photo_url" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->user->policy->number}}</x-ui.text>
            </div>
        </div>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->doctor->user->photo_url" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->doctor->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->doctor->specialty->name}}</x-ui.text>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-4">
        <x-ui.heading class="flex" level="h3" size="sm">
            <x-ui.icon name="star" class="self-center" />
            <x-ui.text class="text-lg ml-2">Como califica la atención que recibio?</x-ui.text>
        </x-ui.heading>

        <div class="flex justify-center pt-2">
            <div x-data="{ hover: 0, rating: @entangle('rating') }" class="flex items-center gap-1">
                @for ($i = 1; $i <= 5; $i++)
                <svg
                    wire:click="rate({{ $i }})"
                    @mouseenter="hover = {{ $i }}"
                    @mouseleave="hover = 0"
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-8 h-8 cursor-pointer transition"
                    :class="(hover >= {{ $i }} || rating >= {{ $i }})
                        ? 'text-yellow-400 scale-110'
                        : 'text-gray-300'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.963a1 1 0 00.95.69h4.167c.969 0 1.371 1.24.588 1.81l-3.372 2.45a1 1 0 00-.364 1.118l1.287 3.964c.3.921-.755 1.688-1.54 1.118l-3.372-2.45a1 1 0 00-1.176 0l-3.372 2.45c-.784.57-1.838-.197-1.539-1.118l1.287-3.964a1 1 0 00-.364-1.118L2.98 9.39c-.783-.57-.38-1.81.588-1.81h4.167a1 1 0 00.95-.69l1.286-3.963z"/>
                </svg>
                @endfor
            </div>
            <x-ui.error name="rating" />
        </div>

        <div class="mx-auto pt-4">
            <x-ui.textarea wire:model="comments" placeholder="Deje un comentario"/>
            <x-ui.error name="comments" />
        </div>

        <div class="flex justify-center mt-4">
            <x-ui.button class="w-40 mr-1" wire:click="save" variant="outline" color="blue" icon="check">
                Guardar
            </x-ui.button>
        </div>
    </x-ui.card>
</div>
