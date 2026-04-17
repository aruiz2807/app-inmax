<x-ui.modal
    id="dispensation-details-modal"
    animation="fade"
    width="2xl"
    heading="Detalle de dispensación"
    description="Medicamentos recetados para el appointment seleccionado"
    x-on:open-dispensation-details-modal.window="$data.open()"
    x-on:close-dispensation-details-modal.window="$data.close()"
>
    @if($selectedAppointment)
        <div class="space-y-4">
            <div class="grid gap-2 md:grid-cols-2 text-sm">
                <p><span class="font-semibold">Paciente:</span> {{ $selectedAppointment['patient_name'] }}</p>
                <p><span class="font-semibold">No. Membresía:</span> {{ $selectedAppointment['membership_number'] }}</p>
                <p><span class="font-semibold">Médico prescriptor:</span> {{ $selectedAppointment['prescriber_doctor'] }}</p>
                <p><span class="font-semibold">Fecha consulta:</span> {{ $selectedAppointment['appointment_date_label'] }}</p>
                <p><span class="font-semibold">Estatus:</span> {{ $selectedAppointment['is_dispensed'] ? 'Surtida' : 'Pendiente' }}</p>
                <p><span class="font-semibold">Fecha de surtido:</span> {{ $selectedAppointment['dispensed_at_label'] }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-neutral-200 dark:border-neutral-700 rounded-lg overflow-hidden">
                    <thead class="bg-neutral-100 dark:bg-neutral-800">
                        <tr>
                            <th class="text-left px-3 py-2 font-semibold">Medicamento</th>
                            <th class="text-left px-3 py-2 font-semibold">Presentación</th>
                            <th class="text-left px-3 py-2 font-semibold">Dosis</th>
                            <th class="text-left px-3 py-2 font-semibold">Cantidad</th>
                            <th class="text-left px-3 py-2 font-semibold">Indicaciones</th>
                            <th class="text-left px-3 py-2 font-semibold">Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedAppointment['prescribed_medications'] as $medication)
                            <tr class="border-t border-neutral-200 dark:border-neutral-700">
                                <td class="px-3 py-2">{{ $medication['name'] }}</td>
                                <td class="px-3 py-2">{{ $medication['presentation'] }}</td>
                                <td class="px-3 py-2">{{ $medication['dose'] }}</td>
                                <td class="px-3 py-2">{{ $medication['quantity'] }}</td>
                                <td class="px-3 py-2">{{ $medication['notes'] }}</td>
                                <td class="px-3 py-2">{{ $medication['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-ui.modal>
