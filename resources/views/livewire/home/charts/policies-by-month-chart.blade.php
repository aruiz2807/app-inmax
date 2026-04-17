<div class="h-80 w-full mb-4">
    <h3 class="text-lg font-medium text-center mb-2">Pólizas Creadas (Últimos 6 Meses)</h3>
    <livewire:livewire-column-chart
        key="{{ $columnChartModel->reactiveKey() }}"
        :column-chart-model="$columnChartModel"
    />
</div>
