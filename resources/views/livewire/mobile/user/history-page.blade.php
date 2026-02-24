<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Historial de consultas</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.tabs variant="non-contained">
            <x-ui.tab.group>
                <x-ui.tab label="Proximas" icon="calendar" />
                <x-ui.tab label="Pasadas" icon="archive-box" />
            </x-ui.tab.group>

            <x-ui.tab.panel>
                <x-ui.card size="full">

                    @if($upcomingAppointments->isEmpty())
                    <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.text class="text-base">No hay citas proximas</x-ui.text>
                    </div>
                    @endif

                    @foreach($upcomingAppointments as $upcoming)
                    <div class="flex flex-col p-4 mb-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <div class="flex">
                            <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                                    <path d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-68-76a12,12,0,1,1-12-12A12,12,0,0,1,140,132Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,132ZM96,172a12,12,0,1,1-12-12A12,12,0,0,1,96,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,140,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,172Z"></path>
                                </svg>
                            </div>
                            <div>
                                <x-ui.text class="text-lg">{{$upcoming->formatted_date}}</x-ui.text>
                                <x-ui.text class="text-sm opacity-50">{{$upcoming->formatted_time}}</x-ui.text>
                            </div>
                        </div>

                        <div class="flex mt-8">
                            <x-ui.avatar size="xl" icon="user" color="teal" src="/img/doctor.png" circle />
                            <div class="pl-4">
                                <x-ui.text class="pt-1 text-xl">{{$upcoming->doctor->user->name}}</x-ui.text>
                                <x-ui.text class="text-base opacity-75">{{$upcoming->doctor->specialty->name}}</x-ui.text>
                            </div>
                        </div>

                        <a href="#" class="flex mt-8">
                            <x-ui.icon name="map-pin" />
                            <x-ui.text class="text-base">{{$upcoming->doctor->address}}</x-ui.text>
                        </a>

                        <x-ui.separator class="mt-2 mb-2"/>

                        <div class="flex justify-center">
                            <x-ui.button wire:click="cancel({{ $upcoming->id }})" variant="outline" color="red" icon="x-circle">
                                Cancelar cita
                            </x-ui.button>
                        </div>
                    </div>
                    @endforeach
                </x-ui.card>
            </x-ui.tab.panel>

            <x-ui.tab.panel>
                <x-ui.card size="full">

                    @if($upcomingAppointments->isEmpty())
                    <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.text class="text-base">No hay citas pasadas</x-ui.text>
                    </div>
                    @endif

                    @foreach($pastAppointments as $past)
                    <div class="flex flex-col p-4 mb-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <div class="flex justify-center mb-4">
                            <x-ui.badge :icon="$past->status_icon" variant="outline" :color="$past->status_color" pill>{{$past->formatted_status}}</x-ui.badge>
                        </div>

                        <div class="flex">
                            <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                                    <path d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-68-76a12,12,0,1,1-12-12A12,12,0,0,1,140,132Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,132ZM96,172a12,12,0,1,1-12-12A12,12,0,0,1,96,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,140,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,172Z"></path>
                                </svg>
                            </div>
                            <div>
                                <x-ui.text class="text-lg">{{$past->formatted_date}}</x-ui.text>
                                <x-ui.text class="text-sm opacity-50">{{$past->formatted_time}}</x-ui.text>
                            </div>
                        </div>

                        <div class="flex mt-8">
                            <x-ui.avatar size="xl" icon="user" color="teal" src="/img/doctor.png" circle />
                            <div class="pl-4">
                                <x-ui.text class="pt-1 text-xl">{{$past->doctor->user->name}}</x-ui.text>
                                <x-ui.text class="text-base opacity-75">{{$past->doctor->specialty->name}}</x-ui.text>
                            </div>
                        </div>

                        <a href="#" class="flex mt-8">
                            <x-ui.icon name="map-pin" />
                            <x-ui.text class="text-base">{{$past->doctor->address}}</x-ui.text>
                        </a>

                        @if($past->status === 'Completed')
                        <x-ui.separator class="mt-2 mb-2"/>

                        <div class="flex justify-center">
                            <x-ui.button wire:click="open({{ $past->id }})" variant="outline" color="teal" icon="clipboard">
                                Nota medica
                            </x-ui.button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </x-ui.card>
            </x-ui.tab.panel>
        </x-ui.tabs>
    </div>

    <x-ui.modal
        id="cancel-modal"
        animation="fade"
        width="md"
        heading="Cancelar cita"
        description="Está seguro que desea cancelar esta cita?"
        x-on:open-cancel-modal.window="$data.open()"
        x-on:close-cancel-modal.window="$data.close()"
    >
        <div class="flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
                Cancelar
            </x-ui.button>

            <x-ui.button color="teal" icon="check" wire:click="confirmCancel">
                Confirmar
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
