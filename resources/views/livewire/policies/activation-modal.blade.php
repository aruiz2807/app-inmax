<x-ui.modal
    id="activation-modal"
    animation="fade"
    width="2xl"
    heading="Activar póliza"
    description="Está seguro que desea activar esta póliza?"
    x-on:open-activation-modal.window="$data.open()"
    x-on:close-activation-modal.window="$data.close()"
>
    <x-ui.text class="mb-2 font-semibold text-base">
        Miembro: {{$policy_user_name}}
    </x-ui.text>

    <x-ui.text class="mb-4 font-semibold text-base">
        Número de membresía: {{$policy_number}}
    </x-ui.text>

    @if(!$reactivation)
    <x-ui.fieldset label="Informacion del pago">
        <x-ui.field required>
            <x-ui.label>Metodo de pago</x-ui.label>
            <x-ui.select
                placeholder="Seleccione el metodo de pago..."
                icon="wallet"
                wire:model="payment_method"
            >
                <x-ui.select.option value="CS">Efectivo</x-ui.select.option>
                <x-ui.select.option value="CC">Tarjeta de credito</x-ui.select.option>
                <x-ui.select.option value="DC">Tarjeta de debito</x-ui.select.option>
                <x-ui.select.option value="TR">Transferencia</x-ui.select.option>
            </x-ui.select>
            <x-ui.error name="form.plan" />
        </x-ui.field>

        <x-ui.field required>
            <x-ui.label>Referencia</x-ui.label>
            <x-ui.input wire:model="payment_reference" name="payment_reference" placeholder="Referencia de pago" />
            <x-ui.error name="form.name" />
        </x-ui.field>

        <x-ui.field>
            <x-ui.label>Comprobante</x-ui.label>
            <input type="file" wire:model="payment_attachment" placeholder="Seleccione un archivo para adjuntar" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"/>
            <x-ui.error name="payment_atachment" />
            <div wire:loading wire:target="payment_attachment">
                Subiendo archivo...
            </div>
        </x-ui.field>
    </x-ui.fieldset>
    @endif

    <div class="flex justify-end gap-3 pt-4">
        <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
            Cancelar
        </x-ui.button>

        <x-ui.button
            color="teal"
            icon="check"
            wire:click="confirmActivation"
            wire:loading.attr="disabled"
            wire:target="confirmActivation"
            x-bind:disabled="!$wire.payment_method || !$wire.payment_reference"
        >
            Confirmar
        </x-ui.button>
    </div>
</x-ui.modal>
