<x-mobile-layout>
    <div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
        <div class="relative w-full">
            <img src="/img/home.png" alt="Header" class="w-full object-cover">

            <!-- User Profile Button -->
            <a href="{{ route('user.my-profile') }}"
            class="absolute -bottom-0 left-3 bg-white p-3 rounded-full shadow-lg border border-gray-100 hover:shadow-xl transition">
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
            </a>
        </div>

        <div class="px-6 pt-12 pb-8">
            <h1 class="text-2xl font-bold text-center text-[#1A3A5A] mb-8">
                Hola! Bienvenido a tu INMAX!
            </h1>

            <div class="space-y-4">

                <x-ui.card size="full">
                    <x-ui.heading class="flex justify-center" level="h3" size="sm">
                        <x-ui.icon name="calendar" class="self-center" />
                        <x-ui.text class="text-lg ml-2">Consultas de hoy</x-ui.text>
                    </x-ui.heading>

                    @foreach ($todayAppointments as $appointment)
                    <div class="grid grid-cols-[auto_6rem] justify-stretch items-center p-4">
                        <x-ui.text class="text-base">{{ $appointment->user->name }}</x-ui.text>
                        <x-ui.text class="text-base opacity-50">{{ $appointment->time->format('h:i A') }}</x-ui.text>
                    </div>
                    @endforeach
                </x-ui.card>

                <a href="{{ route('doctor.history') }}" class="flex items-center p-4 bg-[#E3F2FD] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <div class="p-3 bg-[#2D4356] rounded-xl text-white mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-800">Ver historial de consultas</span>
                </a>
            </div>
        </div>
    </div>
</x-mobile-layout>
