<div class="flex flex-col gap-3 pb-3 border-b border-neutral-100 mb-3 lg:flex-row lg:items-end lg:justify-between">
    <div class="flex flex-wrap items-end gap-2">
        <div>
            <label for="dispensation-date-from" class="block text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">Desde</label>
            <input
                id="dispensation-date-from"
                type="date"
                wire:model.live="dateFrom"
                class="h-9 px-3 text-sm rounded-md border border-neutral-300 text-neutral-700 focus:border-teal-500 focus:ring-teal-500"
            >
        </div>

        <div>
            <label for="dispensation-date-to" class="block text-xs font-semibold text-neutral-500 uppercase tracking-wide mb-1">Hasta</label>
            <input
                id="dispensation-date-to"
                type="date"
                wire:model.live="dateTo"
                class="h-9 px-3 text-sm rounded-md border border-neutral-300 text-neutral-700 focus:border-teal-500 focus:ring-teal-500"
            >
        </div>

        <button
            type="button"
            wire:click="clearDateRange"
            class="h-9 px-3 text-xs font-medium rounded-md border border-neutral-300 text-neutral-600 hover:border-neutral-400 hover:bg-neutral-50 transition-colors"
        >
            Limpiar
        </button>
    </div>

    <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wide">Acceso rapido:</span>

        <button
            type="button"
            wire:click="applyPreset('last7')"
            class="px-3 py-1 text-xs font-medium rounded-full border border-neutral-300 text-neutral-600 hover:border-teal-500 hover:text-teal-600 hover:bg-teal-50 transition-colors"
        >
            Ultimos 7 dias
        </button>

        <button
            type="button"
            wire:click="applyPreset('month')"
            class="px-3 py-1 text-xs font-medium rounded-full border border-neutral-300 text-neutral-600 hover:border-teal-500 hover:text-teal-600 hover:bg-teal-50 transition-colors"
        >
            Este mes
        </button>
    </div>
</div>
