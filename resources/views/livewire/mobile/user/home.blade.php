
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

    <!-- Notification Bell -->
    <div class="absolute bottom-0 right-3">
        <x-dropdown align="right" width="60" contentClasses="p-0 overflow-hidden" dropdownClasses="w-80 max-w-[calc(100vw-1.5rem)]">
            <x-slot name="trigger">
                <button class="relative flex items-center justify-center size-9 border-2 border-white rounded-full shadow-md bg-white/20 hover:bg-white/40 transition focus:outline-none focus:border-neutral-300">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-[#1A3A5A]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
                        />
                    </svg>

                    @if($unratedAppointmentsCount > 0)
                        <span class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-semibold text-white bg-red-500 rounded-full">
                            {{ $unratedAppointmentsCount > 99 ? '99+' : $unratedAppointmentsCount }}
                        </span>
                    @endif
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="bg-white">
                    <div class="max-h-96 overflow-y-auto">
                        @forelse($overflowUnratedAppointments as $appointment)
                            <div class="flex items-start gap-3 px-4 py-3 border-b border-neutral-100 last:border-b-0">
                                <a href="{{ route('user.rating', $appointment->id) }}" class="flex-1 block hover:opacity-90 transition">
                                    <p class="text-sm font-semibold text-[#1A3A5A]">
                                        @if($appointment->doctor->type === \App\Enums\DoctorType::Doctor)
                                            ¡Califica tu consulta!
                                        @else
                                            ¡Califica el servicio!
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-neutral-600 leading-5">
                                        Tu cita del <strong>{{ $appointment->formatted_date }}</strong> con
                                        @if($appointment->doctor->type === \App\Enums\DoctorType::Doctor)
                                            el <strong>Dr. {{ $appointment->doctor->user->name }}</strong>
                                        @else
                                            <strong>{{ $appointment->doctor->user->name }}</strong>
                                        @endif
                                        aún no ha sido calificada.
                                    </p>
                                </a>

                                <button type="button" wire:click="dismissRatingAlert({{ $appointment->id }})" class="mt-0.5 p-1 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 rounded-full transition">
                                    <x-ui.icon name="x-mark" variant="mini" class="size-5" />
                                </button>
                            </div>
                        @empty
                            <div class="px-4 py-6 text-center text-sm text-neutral-500">
                                No tienes notificaciones adicionales.
                            </div>
                        @endforelse
                    </div>
                </div>
            </x-slot>
        </x-dropdown>
    </div>
</div>

    <div class="px-6 pt-12 pb-8">
        <h1 class="text-2xl font-bold text-center text-[#1A3A5A] mb-8">
            ¡Hola {{ auth()->user()->name }}! ¡Bienvenido a tu INMAX!
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

            <a href="{{ route('user.ambulance') }}" class="flex items-center p-4 bg-[#FDECEE] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="p-3 bg-[#EA4F58] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 3h8v5h5v8h-5v5H8v-5H3V8h5V3z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800">Solicitar ambulancia</span>
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

            <a href="{{ $whatsappUrl ?? '#' }}" target="_blank" rel="noopener noreferrer" class="flex items-center p-4 bg-[#E0F7F4] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50 group">
                <div class="p-3 bg-[#4CAF7D] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M13.601 2.326A7.854 7.854 0 0 0 8.132 0C3.753 0 .194 3.548.193 7.926c0 1.4.366 2.768 1.057 3.965L0 16l4.162-1.088a7.94 7.94 0 0 0 3.97 1.059h.003c4.378 0 7.937-3.548 7.938-7.926a7.9 7.9 0 0 0-2.472-5.719zM8.135 14.66h-.002a6.6 6.6 0 0 1-3.36-.92l-.24-.144-2.468.646.66-2.407-.157-.248a6.6 6.6 0 0 1-1.013-3.5c.001-3.625 2.957-6.575 6.587-6.575a6.56 6.56 0 0 1 4.647 1.925 6.56 6.56 0 0 1 1.927 4.648c-.001 3.626-2.957 6.575-6.588 6.575zm3.615-4.934c-.198-.099-1.17-.578-1.352-.645-.182-.066-.315-.099-.448.1-.132.198-.513.644-.628.776-.115.132-.23.149-.429.05-.198-.1-.837-.308-1.594-.981-.589-.525-.986-1.173-1.101-1.371-.115-.198-.012-.305.087-.404.09-.089.198-.231.297-.347.099-.115.132-.198.198-.33.066-.132.033-.248-.017-.347-.05-.099-.448-1.08-.613-1.479-.161-.387-.325-.334-.448-.34a7 7 0 0 0-.38-.01c-.132 0-.347.05-.53.248-.182.198-.694.678-.694 1.653 0 .975.71 1.918.81 2.05.099.132 1.393 2.128 3.376 2.982.472.204.84.326 1.127.417.474.151.905.13 1.246.079.38-.057 1.17-.479 1.336-.942.165-.462.165-.859.116-.942-.05-.083-.182-.132-.38-.231z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-800 flex-1">Whatsapp</span>
            </a>

        </div>
    </div>
</div>
