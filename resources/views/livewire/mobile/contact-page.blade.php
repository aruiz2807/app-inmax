<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Contacto</x-ui.text>
    </div>

    <div class="relative w-full">
        <x-ui.card size="full">
            <div class="flex flex-col items-center">
                <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                        <path d="M201.89,54.66A103.43,103.43,0,0,0,128.79,24H128A104,104,0,0,0,24,128v56a24,24,0,0,0,24,24H64a24,24,0,0,0,24-24V144a24,24,0,0,0-24-24H40.36A88.12,88.12,0,0,1,190.54,65.93,87.39,87.39,0,0,1,215.65,120H192a24,24,0,0,0-24,24v40a24,24,0,0,0,24,24h24a24,24,0,0,1-24,24H136a8,8,0,0,0,0,16h56a40,40,0,0,0,40-40V128A103.41,103.41,0,0,0,201.89,54.66ZM64,136a8,8,0,0,1,8,8v40a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V136Zm128,56a8,8,0,0,1-8-8V144a8,8,0,0,1,8-8h24v56Z"></path>
                    </svg>
                </div>

                <x-ui.text class="text-lg font-semibold">Necesitas ayuda?</x-ui.text>

                <x-ui.text class="w-2/3 text-base mt-4">Comunicate con nosotros por alguno de los siguientes medios</x-ui.text>

                <div class="w-2/3 flex flex-col p-4 mt-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <div class="flex">
                        <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                                <path d="M144.27,45.93a8,8,0,0,1,9.8-5.66,86.22,86.22,0,0,1,61.66,61.66,8,8,0,0,1-5.66,9.8A8.23,8.23,0,0,1,208,112a8,8,0,0,1-7.73-5.94,70.35,70.35,0,0,0-50.33-50.33A8,8,0,0,1,144.27,45.93Zm-2.33,41.8c13.79,3.68,22.65,12.54,26.33,26.33A8,8,0,0,0,176,120a8.23,8.23,0,0,0,2.07-.27,8,8,0,0,0,5.66-9.8c-5.12-19.16-18.5-32.54-37.66-37.66a8,8,0,1,0-4.13,15.46Zm81.94,95.35A56.26,56.26,0,0,1,168,232C88.6,232,24,167.4,24,88A56.26,56.26,0,0,1,72.92,32.12a16,16,0,0,1,16.62,9.52l21.12,47.15,0,.12A16,16,0,0,1,109.39,104c-.18.27-.37.52-.57.77L88,129.45c7.49,15.22,23.41,31,38.83,38.51l24.34-20.71a8.12,8.12,0,0,1,.75-.56,16,16,0,0,1,15.17-1.4l.13.06,47.11,21.11A16,16,0,0,1,223.88,183.08Zm-15.88-2s-.07,0-.11,0h0l-47-21.05-24.35,20.71a8.44,8.44,0,0,1-.74.56,16,16,0,0,1-15.75,1.14c-18.73-9.05-37.4-27.58-46.46-46.11a16,16,0,0,1,1-15.7,6.13,6.13,0,0,1,.57-.77L96,95.15l-21-47a.61.61,0,0,1,0-.12A40.2,40.2,0,0,0,40,88,128.14,128.14,0,0,0,168,216,40.21,40.21,0,0,0,208,181.07Z"></path>
                            </svg>
                        </div>
                        <div>
                            <x-ui.text class="text-lg">Llamanos</x-ui.text>
                            <x-ui.text class="text-sm opacity-50">3300000000</x-ui.text>
                        </div>
                    </div>
                </div>

                <div class="w-2/3 flex flex-col p-4 mt-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <div class="flex">
                        <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                                <path d="M224,48H32a8,8,0,0,0-8,8V192a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A8,8,0,0,0,224,48Zm-96,85.15L52.57,64H203.43ZM98.71,128,40,181.81V74.19Zm11.84,10.85,12,11.05a8,8,0,0,0,10.82,0l12-11.05,58,53.15H52.57ZM157.29,128,216,74.18V181.82Z"></path>
                            </svg>
                        </div>
                        <div>
                            <x-ui.text class="text-lg">Envianos un email</x-ui.text>
                            <x-ui.text class="text-sm opacity-50">contacto@inmax-sure.com</x-ui.text>
                        </div>
                    </div>
                </div>

                <div class="w-2/3 flex flex-col p-4 mt-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                    <div class="flex">
                        <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                                <path d="M187.58,144.84l-32-16a8,8,0,0,0-8,.5l-14.69,9.8a40.55,40.55,0,0,1-16-16l9.8-14.69a8,8,0,0,0,.5-8l-16-32A8,8,0,0,0,104,64a40,40,0,0,0-40,40,88.1,88.1,0,0,0,88,88,40,40,0,0,0,40-40A8,8,0,0,0,187.58,144.84ZM152,176a72.08,72.08,0,0,1-72-72A24,24,0,0,1,99.29,80.46l11.48,23L101,118a8,8,0,0,0-.73,7.51,56.47,56.47,0,0,0,30.15,30.15A8,8,0,0,0,138,155l14.61-9.74,23,11.48A24,24,0,0,1,152,176ZM128,24A104,104,0,0,0,36.18,176.88L24.83,210.93a16,16,0,0,0,20.24,20.24l34.05-11.35A104,104,0,1,0,128,24Zm0,192a87.87,87.87,0,0,1-44.06-11.81,8,8,0,0,0-6.54-.67L40,216,52.47,178.6a8,8,0,0,0-.66-6.54A88,88,0,1,1,128,216Z"></path>
                            </svg>
                        </div>
                        <div>
                            <x-ui.text class="text-lg">Chatea con nosotros</x-ui.text>
                            <x-ui.text class="text-sm opacity-50">3300000000</x-ui.text>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
