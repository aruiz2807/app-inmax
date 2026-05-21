<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <a href="{{ route('doctor.home') }}">
            <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" />
        </a>
        <x-ui.text class="text-2xl">Nota medica</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="calendar" variant="solid" class="self-center" />
                <x-ui.text class="text-lg ml-2">Cita finalizada!</x-ui.text>
            </x-ui.heading>

            <div class="flex justify-center mt-10 mb-10">
                <x-ui.icon name="check-circle" variant="solid" class="fill-teal-500 size-16"/>
            </div>

            <div class="flex flex-col bg-[#E3F2FD] rounded-xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Paciente : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->appointment->user->name }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Membresía : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->appointment->user->policy->number }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>{{ $note->appointment->doctor->type === \App\Enums\DoctorType::Doctor ? 'Doctor : ' : 'Proveedor : '}}</x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->appointment->doctor->user->name }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Servicios realizados</x-ui.text>
                    <table class="items">
                        @foreach ($note->appointment->services as $service)
                            @if($service->status === 'Completed')
                            <tr>
                                <td class="desc">{!! nl2br(e($service->service->name)) !!}</td>
                                <td class="price">
                                    <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>
                                        {{ $service->covered_text }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </table>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[6rem_auto] justify-stretch p-4">
                    <x-ui.text>Fecha : </x-ui.text>
                    <x-ui.text class="font-semibold">{{ $note->created_at->format('d/m/Y') }} {{ $note->created_at->format('h:i A') }}</x-ui.text>
                </div>
            </div>


            @if($note->appointment->subtotal > 0)
            <div class="mt-4 flex flex-col bg-[#E3F2FD] rounded-xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="grid grid-cols-[10rem_auto] justify-stretch p-4">
                    <x-ui.text>Total cuenta : </x-ui.text>
                    <x-ui.text class="font-semibold text-right">${{ $note->appointment->subtotal }}</x-ui.text>
                </div>

                @if($note->appointment->coupon_discount > 0)
                <x-ui.separator />

                <div class="grid grid-cols-[10rem_auto] justify-stretch p-4">
                    <x-ui.text>Descuento cupón : </x-ui.text>
                    <x-ui.text class="font-semibold text-right">${{ $note->appointment->coupon_discount }}</x-ui.text>
                </div>
                @endif

                <x-ui.separator />

                <div class="grid grid-cols-[10rem_auto] justify-stretch p-4">
                    <x-ui.text>Cobro al paciente : </x-ui.text>
                    <x-ui.text class="font-semibold text-right">${{ $note->appointment->user_payment }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[10rem_auto] justify-stretch p-4">
                    <x-ui.text>Comisión Inmax : </x-ui.text>
                    <x-ui.text class="font-semibold text-right">${{ $note->appointment->commission }}</x-ui.text>
                </div>

                <x-ui.separator />

                <div class="grid grid-cols-[10rem_auto] justify-stretch p-4">
                    <x-ui.text>Ganancia del socio : </x-ui.text>
                    <x-ui.text class="font-semibold text-right">${{ $note->appointment->total }}</x-ui.text>
                </div>
            </div>
            @endif

            @if($note->appointment->doctor->type === \App\Enums\DoctorType::Doctor)
            <div class="flex justify-center mt-4">
                <x-ui.button class="w-40 mr-1" wire:click="schedule" variant="outline" color="blue" icon="calendar">
                    Agendar
                </x-ui.button>

                <x-ui.button class="w-40 mr-1" wire:click="print" variant="outline" color="indigo" icon="document">
                    Receta digital
                </x-ui.button>
            </div>
            @endif

            @if(!$hasReceptionistAssigned)
            <div class="flex justify-center mt-4">
                <x-ui.button class="w-40 mr-1" wire:click="print_ticket" variant="outline" color="indigo" icon="document">
                    Ticket
                </x-ui.button>
            </div>
            @endif
        </x-ui.card>
    </div>
</div>
