<div>
    <x-slot name="header">
        {{ __('app.purchases') }}
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Historial de compras</span>

            <x-ui.modal.trigger id="upload-invoice-modal" wire:click="resetUploadForm">
                <x-ui.button type="button" icon="arrow-up-tray" color="teal">
                        Cargar factura
                </x-ui.button>
            </x-ui.modal.trigger>
        </x-ui.heading>

        <p class="mb-4">Consulte las compras de medicamentos registradas en el sistema</p>
    </x-ui.card>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:clerk.purchases-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="upload-invoice-modal"
        animation="fade"
        width="2xl"
        heading="Cargar factura"
        description="Seleccione el proveedor y cargue ambos archivos de respaldo (PDF y XML)"
        x-on:open-upload-invoice-modal.window="$data.open()"
        x-on:close-upload-invoice-modal.window="$data.close()"
    >
        <form wire:submit="uploadInvoice">
            <x-ui.fieldset label="Informacion de la factura">
                <x-ui.field required>
                    <x-ui.label>Proveedor</x-ui.label>
                    <x-ui.select wire:model.live="uploadSupplierId" placeholder="Seleccione un proveedor">
                        @foreach($suppliers as $supplier)
                            <x-ui.select.option value="{{ $supplier->id }}">
                                {{ $supplier->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error name="uploadSupplierId" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Archivo PDF</x-ui.label>
                    <input
                        type="file"
                        wire:model="uploadInvoicePdfFile"
                        accept="application/pdf"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                    />
                    <x-ui.error name="uploadInvoicePdfFile" />
                    <div wire:loading wire:target="uploadInvoicePdfFile" class="text-sm text-neutral-500 mt-2">
                        Subiendo archivo...
                    </div>
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Archivo XML</x-ui.label>
                    <input
                        type="file"
                        wire:model="uploadInvoiceXmlFile"
                        accept="text/xml,application/xml,.xml"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                    />
                    <x-ui.error name="uploadInvoiceXmlFile" />
                    <div wire:loading wire:target="uploadInvoiceXmlFile" class="text-sm text-neutral-500 mt-2">
                        Subiendo archivo...
                    </div>
                </x-ui.field>
            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button type="button" x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancelar
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar factura
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal
        id="purchase-details-modal"
        animation="fade"
        width="5xl"
        heading="Detalle de factura"
        description="Revise la informacion general y los medicamentos incluidos en la compra"
        x-on:open-purchase-details-modal.window="$data.open()"
        x-on:close-purchase-details-modal.window="$data.close()"
    >
        @if($selectedPurchase)
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-ui.label>Folio</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedPurchase->invoice ?: '-' }}</x-ui.text>
                    </div>

                    <div>
                        <x-ui.label>Proveedor</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedPurchase->supplier?->name ?? 'Sin proveedor' }}</x-ui.text>
                    </div>

                    <div>
                        <x-ui.label>Subtotal</x-ui.label>
                        <x-ui.text class="font-semibold">${{ number_format((float) $selectedPurchase->subtotal, 2) }}</x-ui.text>
                    </div>

                    <div>
                        <x-ui.label>Total</x-ui.label>
                        <x-ui.text class="font-semibold">${{ number_format((float) $selectedPurchase->total, 2) }}</x-ui.text>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-neutral-50 text-neutral-600">
                            <tr>
                                <th class="px-4 py-3 text-center font-semibold"></th>
                                <th class="px-4 py-3 text-left font-semibold">Medicamento</th>
                                <th class="px-4 py-3 text-left font-semibold">EAN</th>
                                <th class="px-4 py-3 text-right font-semibold">Solicitado</th>
                                <th class="px-4 py-3 text-right font-semibold">Recibido</th>
                                <th class="px-4 py-3 text-right font-semibold">Importe</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-neutral-200">
                            @forelse($selectedPurchase->details as $detail)
                                <tr>
                                    <td class="px-4 py-3 text-center">
                                        @if ($detail->medication?->trade_name != '')
                                            
                                        @else
                                            <span class="bg-red-100 text-red-600 px-2 py-1 rounded-full text-xs font-semibold"> Nuevo </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-medium"> {{ $detail->medication?->name ?? 'Sin nombre' }} </td>
                                    <td class="px-4 py-3">{{ $detail->medication?->ean_code ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">{{ $detail->requested_quantity }}</td>
                                    <td class="px-4 py-3 text-right">{{ $detail->received_quantity }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format((float) $detail->price, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-neutral-500">
                                        Esta compra no tiene detalles registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="w-full flex justify-end gap-3 pt-2">
                    <x-ui.button
                        type="button"
                        x-on:click="$data.close();"
                        wire:click="resetPurchaseDetails"
                        icon="x-mark"
                        variant="outline"
                    >
                        Cerrar
                    </x-ui.button>
                </div>
            </div>
        @endif
    </x-ui.modal>
</div>
