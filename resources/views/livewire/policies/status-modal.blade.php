<x-ui.modal
    id="status-modal"
    animation="fade"
    width="2xl"
    heading="Estatus de la membresía"
    description="Uso de los servicios incluidos en la membresia"
    x-on:open-status-modal.window="$data.open()"
    x-on:close-status-modal.window="$data.close()"
>
    <x-ui.text class="mb-2 font-semibold text-base">
        Miembro: {{$policy_user_name}}
    </x-ui.text>

    <x-ui.text class="mb-4 font-semibold text-base">
        Número de membresía: {{$policy_number}}
    </x-ui.text>

    <div class="relative w-full">
        <x-ui.card size="full">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="shield-check" class="self-center" />
                <x-ui.text class="text-lg ml-2">Uso de la Membresía</x-ui.text>
            </x-ui.heading>

            <div class="flex justify-center pt-2">
                <x-ui.badge :icon="$icon" variant="outline" color="blue" pill>{{ $policy_type }}</x-ui.badge>
            </div>

            <x-ui.slider
                wire:model="percentage"
                handleVariant="circle"
                class="pointer-events-none w-5/6 pl-14"
                tooltips
                :fill-track="[true, false]"
                x-init="$slider.formatTooltipUsing((value) => value.toFixed() + '%')"
            />

            <x-ui.text class="mt-2 mb-4 text-sm opacity-50 text-center">
                {{ $total_used }} de {{ $total_included }} servicios utilizados
            </x-ui.text>

            <a href="#" class="w-full mt-4 flex flex-col bg-[#E3F2FD] rounded-xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
            @foreach ($services as $service)
                @php
                    $doctorName = null;
                    if ($service->doctor_service_id) {
                        $name = $service->doctorService->service->name ?? 'N/A';
                        $doctorName = $service->doctorService->doctor->user->name ?? null;
                    } elseif ($service->doctor_coupon_id) {
                        $name = $service->doctorCoupon->coupon->name ?? 'N/A';
                        $doctorName = $service->doctorCoupon->doctor->user->name ?? null;
                    } elseif ($service->coupon_id) {
                        $name = $service->coupon->name ?? 'N/A';
                    } else {
                        $name = $service->service->name ?? 'N/A';
                    }
                @endphp
                <div class="grid grid-cols-[auto_6rem_2rem] justify-stretch items-center p-4">
                    <div class="flex flex-col pr-2">
                        <x-ui.text>{{ $name }}</x-ui.text>
                        @if($doctorName)
                            <x-ui.text size="xs" class="text-slate-500">{{ $doctorName }}</x-ui.text>
                        @endif
                    </div>
                    <x-ui.text class="opacity-50">{{ $service->used }} de {{ $service->included }} usados</x-ui.text>
                    <x-ui.icon :name="$service->level" :class="'justify-self-end '.$service->color" />
                </div>

                @unless ($loop->last)
                    <x-ui.separator />
                @endunless
            @endforeach
            </a>
        </x-ui.card>

        <x-ui.card size="full" class="mt-4">
            <x-ui.heading class="flex justify-center" level="h3" size="sm">
                <x-ui.icon name="information-circle" class="self-center" />
                <x-ui.text class="text-lg ml-2">Servicios fuera del plan</x-ui.text>
            </x-ui.heading>

            <x-ui.text class="mt-4 mb-2  text-base text-center">
                {{ $total_extra }} servicios utilizados
            </x-ui.text>
        </x-ui.card>
    </div>
</x-ui.modal>