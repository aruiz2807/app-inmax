<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Hola {{ $user->name }}, bienvenido</h1>
            <p class="text-sm text-neutral-500">Este es el home para desktop del doctor.</p>
        </div>

        <a href="{{ route('doctor.my-profile') }}" class="text-sm font-medium text-sky-700 hover:text-sky-900 transition">
            Ver perfil
        </a>
    </div>

    @if($showRequestsAlert && $pendingRequestsCount > 0)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="font-semibold text-amber-900">Solicitudes pendientes</p>
                    <p class="text-sm text-amber-800">
                        Tienes {{ $pendingRequestsCount }} solicitudes pendientes de revisar.
                    </p>
                    <a href="{{ route('doctor.requests') }}" class="mt-2 inline-block text-sm font-semibold text-amber-900 underline">
                        Ir a solicitudes
                    </a>
                </div>

                <button type="button" wire:click="dismissRequestsAlert" class="text-sm text-amber-900 hover:opacity-70 transition">
                    Cerrar
                </button>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-neutral-200 bg-white p-4">
        <h2 class="text-lg font-semibold text-neutral-900">Consultas de hoy</h2>

        @if($todayAppointments->isEmpty())
            <p class="mt-3 text-sm text-neutral-500">No hay citas para hoy.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">Paciente</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">Edad</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">Hora</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white">
                        @foreach ($todayAppointments as $appointment)
                            <tr>
                                <td class="px-4 py-3 text-sm text-neutral-900">{{ $appointment->user->name }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-700">{{ $appointment->user->age }} años</td>
                                <td class="px-4 py-3 text-sm text-neutral-700">{{ $appointment->time->format('h:i A') }}</td>
                                <td class="px-4 py-3 text-sm text-neutral-700">{{ $appointment->date->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('doctor.history') }}" class="inline-flex items-center rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800 transition">
            Ver historial
        </a>

        @if ($user->doctor->specialty_id != $paramGMSpeciality->value || $user->doctor->type === \App\Enums\DoctorType::Lab || $user->doctor->type === \App\Enums\DoctorType::Hospital)
            <a href="{{ route('doctor.requests') }}" class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 transition">
                Ver solicitudes
            </a>
        @endif
    </div>
</div>
