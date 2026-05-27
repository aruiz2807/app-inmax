<div>
    <div class="relative w-full">
        <img src="/img/home.png" alt="Header" class="w-full object-cover">

        <!-- User Profile Button -->
        <div class="absolute bottom-0 left-3">
            <x-dropdown align="left" width="48">
                <x-slot name="trigger">
                    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                        <button class="flex text-sm border-2 border-white rounded-full shadow-md focus:outline-none focus:border-neutral-300 transition">
                            <img class="size-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                        </button>
                    @else
                        <span class="inline-flex rounded-md">
                            <button class="flex text-sm border-2 border-white rounded-full shadow-md focus:outline-none focus:border-neutral-300 transition">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6 text-[#1A3A5A]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"
                                    />
                                </svg>
                            </button>
                        </span>
                    @endif
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link href="{{ route('doctor.my-profile') }}">
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
            Hola {{$user->name}}! Bienvenido a tu INMAX!
        </h1>

        @if($showRequestsAlert && $pendingRequestsCount > 0)
            <div class="mb-6">
                <x-ui.alerts color="orange" icon="exclamation-triangle" class="relative">
                    <x-ui.alerts.heading>
                        ¡Solicitudes pendientes!
                    </x-ui.alerts.heading>
                    
                    <a href="{{ route('doctor.requests') }}" class="block hover:opacity-90 transition">
                        <div class="text-sm">
                            Tienes <strong>{{ $pendingRequestsCount }}</strong> solicitudes de consulta pendientes por aceptar o rechazar. Haz clic aquí para verlas.
                        </div>
                    </a>

                    <x-slot name="controls">
                        <button type="button" wire:click="dismissRequestsAlert" class="p-1 hover:bg-black/5 rounded-full transition">
                            <x-ui.icon name="x-mark" variant="mini" class="size-5" />
                        </button>
                    </x-slot>
                </x-ui.alerts>
            </div>
        @endif

        <div class="space-y-4">

            <x-ui.card size="full">
                <x-ui.heading class="flex justify-center" level="h3" size="sm">
                    <x-ui.icon name="calendar" class="self-center" />
                    <x-ui.text class="text-lg ml-2">Consultas de hoy</x-ui.text>
                </x-ui.heading>

                @if($todayAppointments->isEmpty())
                <div class="flex justify-center mt-2 p-2 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <x-ui.text class="text-base">No hay citas para hoy</x-ui.text>
                </div>
                @endif

                @foreach ($todayAppointments as $appointment)
                <div class="mt-2 p-4 bg-white border border-neutral-200 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <x-ui.text class="text-base font-semibold text-neutral-900">{{ $appointment->user->name }}</x-ui.text>
                            <x-ui.text class="text-sm text-neutral-500 mt-1">{{ $appointment->user->age }} años</x-ui.text>
                        </div>
                        <div class="text-right">
                            <x-ui.text class="text-base font-bold text-[#1A3A5A]">{{ $appointment->time->format('h:i A') }}</x-ui.text>
                            <x-ui.text class="text-xs text-neutral-500 mt-1">{{ $appointment->date->format('d/m/Y') }}</x-ui.text>
                        </div>
                    </div>
                </div>
                @endforeach
            </x-ui.card>

            <a href="{{ route('doctor.history') }}" class="flex items-center p-4 bg-[#E3F2FD] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#2D4356] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Mis consultas y servicios</span>
            </a>

            <a href="{{ route('doctor.results-pending') }}" class="flex items-center p-4 bg-[#E3F2FD] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#2D4356] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Consultas pendientes de resultados</span>
            </a>

            @if ($user->doctor->specialty_id != $paramGMSpeciality->value || $user->doctor->type === \App\Enums\DoctorType::Lab || $user->doctor->type === \App\Enums\DoctorType::Hospital)
            <a href="{{ route('doctor.requests') }}" class="flex items-center p-4 bg-[#E3F2FD] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#F58A71] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Solicitudes pendientes</span>
            </a>
            @endif
        </div>
    </div>
</div>

