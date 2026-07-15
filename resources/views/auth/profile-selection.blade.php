<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-slate-900 text-white pl-4 pr-4">
        <div class="w-full max-w-2xl text-center">
            <div class="flex justify-center mb-6">
                <img src="{{ asset('img/logo.png') }}" class="rounded-full size-16 shadow-lg border-2 border-teal-500" alt="Logo">
            </div>

            <h1 class="text-3xl font-extrabold tracking-tight mb-2">¿Quién va a usar la membresía?</h1>
            <p class="text-slate-400 mb-10 text-sm md:text-base">Selecciona un perfil para continuar.</p>

            <div class="flex flex-wrap justify-center gap-6 md:gap-8">
                @foreach($users as $user)
                    <a href="{{ route('login.profiles.select', $user->id) }}" class="flex flex-col items-center group transition-transform duration-200 transform hover:scale-105 w-24 md:w-28">
                        <!-- Profile Avatar Container -->
                        <div class="relative size-24 md:size-28 rounded-full bg-slate-800 flex items-center justify-center text-3xl font-bold border-4 border-slate-700 group-hover:border-teal-400 group-hover:shadow-[0_0_15px_rgba(45,212,191,0.5)] transition-all duration-300 overflow-hidden shadow-md">
                            @if($user->profile_photo_path)
                                <img src="{{ Storage::disk('public')->url($user->profile_photo_path) }}" class="size-full object-cover">
                            @else
                                <div class="size-full bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center text-white select-none">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                            @endif
                        </div>

                        <!-- Profile Name -->
                        <span class="mt-4 text-base md:text-lg font-semibold text-slate-300 group-hover:text-teal-300 transition-colors duration-200 text-center truncate w-full pl-2 pr-2">
                            {{ $user->name }}
                        </span>

                        <!-- Profile Role Tag -->
                        <span class="mt-1 text-xs text-slate-500 uppercase tracking-wider font-semibold">
                            {{ $user->is_dependent ? 'Dependiente' : 'Titular' }}
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="mt-12 pt-6 border-t border-slate-800">
                <a href="{{ route('login') }}" class="inline-flex items-center text-sm text-slate-400 hover:text-white transition-colors underline decoration-dotted underline-offset-4">
                    <svg class="size-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Regresar al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
