<div class="h-80 w-full mb-4">
    <h3 class="text-lg font-medium text-center mb-2">Pólizas por vendedor (Últimos 6 Meses)</h3>
    <livewire:livewire-pie-chart
        wire:ignore
        key="{{ $pieChartModel->reactiveKey() }}"
        :pie-chart-model="$pieChartModel"
    />
</div>
