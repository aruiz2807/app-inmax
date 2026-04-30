
<div>
    <div class="relative w-full">
        <img src="/img/home.png" alt="Header" class="w-full object-cover">

        <!-- User Profile Button -->
        <div class="ms-3 relative">
            <x-dropdown align="left" width="48">
                <x-slot name="trigger">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <button class="flex text-sm border-2 border-white rounded-full shadow-md focus:outline-none focus:border-neutral-300 transition">
                            <img class="size-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                        </button>
                    @else
                        <span class="inline-flex rounded-md">
                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-neutral-500 bg-white hover:text-neutral-700 focus:outline-none focus:bg-neutral-50 active:bg-neutral-50 transition ease-in-out duration-150">
                                {{ Auth::user()->name }}

                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </span>
                    @endif
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link href="{{ route('user.my-profile') }}">
                        {{ __('app.profile') }}
                    </x-dropdown-link>

                    <div class="col-span-full border-t border-neutral-200"></div>

                    <!-- Authentication -->
                    
                    <form class="col-span-full" method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-1.5 text-sm text-neutral-800 hover:bg-neutral-100 rounded-[calc(var(--dropdown-radius)-var(--dropdown-padding))] transition-colors duration-200 cursor-pointer">
                            {{ __('app.logout') }}
                        </button>
                    </form>
                    
                </x-slot>
            </x-dropdown>
        </div>
    </div>

    <div class="px-6 pt-12 pb-8">
        <h1 class="text-2xl font-bold text-center text-[#1A3A5A] mb-8">
            Hola {{ auth()->user()->name }}! Bienvenido a tu INMAX!
        </h1>

        @foreach($unratedAppointments as $appointment)
            <div class="mb-6" wire:key="unrated-appointment-{{ $appointment->id }}">
                <x-ui.alerts color="orange" icon="exclamation-triangle" class="relative">
                    <x-ui.alerts.heading>
                        @if($appointment->doctor->type === \App\Enums\DoctorType::Doctor)
                            ¡Califica tu consulta!
                        @else
                            ¡Califica el servicio!
                        @endif
                    </x-ui.alerts.heading>

                    <a href="{{ route('user.rating', $appointment->id) }}" class="block hover:opacity-90 transition">
                        <div class="text-sm">
                            Tu cita del <strong>{{ $appointment->formatted_date }}</strong> con
                            @if($appointment->doctor->type === \App\Enums\DoctorType::Doctor)
                                el <strong>Dr. {{ $appointment->doctor->user->name }}</strong>
                            @else
                                <strong>{{ $appointment->doctor->user->name }}</strong>
                            @endif
                            aún no ha sido calificada. Haz clic aquí para calificarla.
                        </div>
                    </a>

                    <x-slot name="controls">
                        <button type="button" wire:click="dismissRatingAlert({{ $appointment->id }})" class="p-1 hover:bg-black/5 rounded-full transition">
                            <x-ui.icon name="x-mark" variant="mini" class="size-5" />
                        </button>
                    </x-slot>
                </x-ui.alerts>
            </div>
        @endforeach

        <div class="space-y-4">

            <a href="{{ route('user.schedule') }}" class="flex items-center p-4 bg-[#E0F7F4] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#4DB6AC] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Programar consulta</span>
            </a>

            <a href="{{ route('user.history') }}" class="flex items-center p-4 bg-[#E3F2FD] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#2D4356] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Mis consultas y servicios</span>
            </a>

            <a href="{{ route('user.record') }}" class="flex items-center p-4 bg-[#FEEBED] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#F58A71] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Mi historial médico</span>
            </a>

            <a href="{{ route('user.status') }}" class="flex items-center p-4 bg-[#E0F7F4] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50 group">
                <div class="p-3 bg-[#4DB6AC] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800 flex-1">Mi uso de membresía</span>
            </a>

        </div>
    </div>
</div>
