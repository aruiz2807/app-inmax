<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
     x-data
     x-on:keydown.escape.window="$wire.closeHistoryUploadForm()">
    <div class="w-full max-w-lg mx-4 bg-white rounded-2xl shadow-xl p-5 pb-8">
        <div class="flex justify-between items-center mb-4">
            <x-ui.text class="text-lg font-semibold">Importar archivo</x-ui.text>
            <button wire:click="closeHistoryUploadForm" class="text-gray-400 hover:text-gray-600">
                <x-ui.icon name="x-mark" class="w-5 h-5" />
            </button>
        </div>

        <form wire:submit.prevent="saveHistoryExternalService" class="flex flex-col gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha estudio</label>
                <input type="date"
                       wire:model="historyUploadDate"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                @error('historyUploadDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titulo <span class="text-red-500">*</span></label>
                <input type="text"
                       wire:model="historyUploadName"
                       placeholder="Ingresa un titulo"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                @error('historyUploadName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                <textarea wire:model="historyUploadComments"
                          placeholder="Comentarios opcionales..."
                          rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                @error('historyUploadComments') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo</label>
                <input type="file"
                       wire:model="historyUploadFile"
                       placeholder="Seleccione un archivo para adjuntar"
                       class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <x-ui.error name="historyUploadFile" />
                <div wire:loading wire:target="historyUploadFile">
                    Subiendo archivo...
                </div>
            </div>

            <div class="flex gap-3 mt-2">
                <x-ui.button type="button" wire:click="closeHistoryUploadForm" variant="outline" color="zinc"
                    class="flex-1 rounded-xl text-sm font-medium">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="outline" color="blue"
                    class="flex-1 rounded-xl text-sm font-medium">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </div>
</div>
