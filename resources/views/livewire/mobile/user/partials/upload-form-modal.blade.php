<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
     x-data
     x-on:keydown.escape.window="$wire.closeUploadForm()">
    <div class="w-full max-w-lg mx-4 bg-white rounded-2xl shadow-xl p-5 pb-8">
        <div class="flex justify-between items-center mb-4">
            <x-ui.text class="text-lg font-semibold">Importar archivo</x-ui.text>
            <button wire:click="closeUploadForm" class="text-gray-400 hover:text-gray-600">
                <x-ui.icon name="x-mark" class="w-5 h-5" />
            </button>
        </div>

        <form wire:submit.prevent="saveExternalService" class="flex flex-col gap-3">
            {{-- Fecha estudio --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha estudio</label>
                <input type="date"
                       wire:model="uploadDate"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                @error('uploadDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Título --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                <input type="text"
                       wire:model="uploadName"
                       placeholder="Ingresa un título"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                @error('uploadName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Comentarios --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                <textarea wire:model="uploadComments"
                          placeholder="Comentarios opcionales..."
                          rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                @error('uploadComments') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Archivo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Archivo</label>
                <input type="file"
                       wire:model="uploadFile"
                       placeholder="Seleccione un archivo para adjuntar"
                       class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <x-ui.error name="uploadFile" />
                <div wire:loading wire:target="uploadFile">
                    Subiendo archivo...
                </div>
            </div>

            <div class="flex gap-3 mt-2">
                <x-ui.button type="button" wire:click="closeUploadForm" variant="outline" color="zinc"
                    class="flex-1 rounded-xl text-sm font-medium">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary" variant="outline" color="blue"
                    class="flex-1 rounded-xl text-sm font-medium">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </div>
</div>
