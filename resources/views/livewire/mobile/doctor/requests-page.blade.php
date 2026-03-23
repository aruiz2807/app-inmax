<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Solicitudes pendientes</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">

            @if($requests->isEmpty())
            <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <x-ui.text class="text-base">No hay citas agendadas</x-ui.text>
            </div>
            @endif

            @foreach($requests as $request)
            <div class="flex flex-col p-2 mb-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">

                <div class="flex mx-auto w-fit">
                    <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                            <path d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-68-76a12,12,0,1,1-12-12A12,12,0,0,1,140,132Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,132ZM96,172a12,12,0,1,1-12-12A12,12,0,0,1,96,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,140,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,172Z"></path>
                        </svg>
                    </div>
                    <div>
                        <x-ui.text class="text-lg">{{$request->formatted_date}}</x-ui.text>
                        <x-ui.text class="text-sm opacity-50">{{$request->formatted_time}}</x-ui.text>
                    </div>
                </div>

                <div class="flex mt-8">
                    <x-ui.avatar size="xl" icon="user" color="teal" :src="$request->user->photo_url" circle />
                    <div class="pl-4">
                        <x-ui.text class="pt-1 text-xl">{{$request->user->name}}</x-ui.text>
                        <x-ui.text class="text-base opacity-75">{{$request->user->policy->number}}</x-ui.text>
                    </div>
                </div>

                <div class="flex mt-4">
                    <x-ui.avatar size="xl" icon="user" color="teal" src="/img/checkup.png" circle />

                    <div class="flex flex-col w-full">
                    @foreach($request->services as $service)
                        <div class="flex items-center justify-between pl-4 pb-2">
                            <x-ui.text class="text-base pr-1">{{$service->service->name}}</x-ui.text>
                            <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>{{$service->covered_text}}</x-ui.badge>
                        </div>
                    @endforeach
                    </div>
                </div>

                <x-ui.separator class="mt-2 mb-2"/>

                <div class="flex justify-center">
                    <x-ui.button class="w-40 mr-1" wire:click="accept({{ $request->id }})" variant="outline" color="teal" icon="check-circle">
                        Acceptar
                    </x-ui.button>

                    <x-ui.button class="w-40 ml-1" wire:click="reject({{ $request->id }})" variant="outline" color="red" icon="x-circle">
                        Rechazar
                    </x-ui.button>
                </div>
            </div>
            @endforeach
        </x-ui.card>
    </div>

    <x-ui.modal
        id="accept-modal"
        animation="fade"
        width="md"
        heading="Aceptar cita"
        description="Si acepta la cita, se añadira a su calendario y al calendario del paciente"
        x-on:open-accept-modal.window="$data.open()"
        x-on:close-accept-modal.window="$data.close()"
    >
        <div class="flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
                Cancelar
            </x-ui.button>

            <x-ui.button color="teal" icon="check" wire:click="acceptAppointment">
                Confirmar
            </x-ui.button>
        </div>
    </x-ui.modal>

    <x-ui.modal
        id="reject-modal"
        animation="fade"
        width="md"
        heading="Rechazar cita"
        description="Si se declina la cita, tendrá que ser reagendada"
        x-on:open-reject-modal.window="$data.open()"
        x-on:close-reject-modal.window="$data.close()"
    >
        <div class="flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
                Cancelar
            </x-ui.button>

            <x-ui.button color="teal" icon="check" wire:click="rejectAppointment">
                Confirmar
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
