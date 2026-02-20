<x-mobile-layout>
    <div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
        <div class="relative w-full">
            <img src="/img/home.png" alt="Header" class="w-full object-cover">
        </div>

        <div class="px-6 pt-12 pb-8">
            <h1 class="text-2xl font-bold text-center text-[#1A3A5A] mb-8">
                Hola! Bienvenido a tu INMAX!
            </h1>

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
                    <span class="text-lg font-bold text-gray-800">Ver historial de consultas</span>
                </a>

                <a href="{{ route('user.record') }}" class="flex items-center p-4 bg-[#FEEBED] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <div class="p-3 bg-[#F58A71] rounded-xl text-white mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-800">Ver expediente médico</span>
                </a>

                <a href="{{ route('user.status') }}" class="flex items-center p-4 bg-[#E0F7F4] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50 group">
                    <div class="p-3 bg-[#4DB6AC] rounded-xl text-white mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-800 flex-1">Mi uso del seguro</span>
                </a>

            </div>
        </div>
    </div>
</x-guest-layout>
