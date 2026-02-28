<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Mi perfil</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <div class="flex flex-col items-center">
                <x-ui.avatar size="xl" icon="user" color="teal" src="/img/doctor.png" circle />

                <x-ui.text class="pt-2 text-xl">{{$user->name}}</x-ui.text>
                <x-ui.text class="pb-2 text-sm opacity-75">{{$user->doctor->specialty->name}}</x-ui.text>

                <x-ui.badge :icon="$user->doctor->status_icon" variant="outline" :color="$user->doctor->status_color" pill>{{$user->doctor->formatted_status}}</x-ui.badge>

                <a href="#" class="w-5/6 mt-8 mb-6 flex flex-col bg-[#E3F2FD] rounded-xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                        <x-ui.text>Cedula : </x-ui.text>
                        <x-ui.text class="font-semibold">{{$user->doctor->license}}</x-ui.text>
                    </div>

                    <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                        <x-ui.text>Universidad : </x-ui.text>
                        <x-ui.text class="font-semibold">{{$user->doctor->university}}</x-ui.text>
                    </div>

                    <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                        <x-ui.text>Email : </x-ui.text>
                        <x-ui.text class="font-semibold">{{$user->email}}</x-ui.text>
                    </div>

                    <x-ui.separator />

                    <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                        <x-ui.text>Telefono : </x-ui.text>
                        <x-ui.text class="font-semibold">{{$user->phone}}</x-ui.text>
                    </div>
                </a>

                <x-ui.button class="w-1/2 mt-4" wire:click="help" variant="outline" color="indigo" icon="question-mark-circle">
                    Necesitas ayuda?
                </x-ui.button>

                <form class="w-1/2 mt-4 mb-2" method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-ui.button class="w-full" type="submit" variant="outline" color="red" icon="arrow-left-start-on-rectangle">
                        {{ __('app.logout') }}
                    </x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
</div>
