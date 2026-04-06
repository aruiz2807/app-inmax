<?php

namespace App\Livewire\Home\Charts;

use App\Models\Policy;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Carbon\Carbon;
use Livewire\Component;

class PoliciesByMonthChart extends Component
{
    public function render()
    {
        // Last 6 months including current month
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        // Get policies grouped by month
        $policies = Policy::query()
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->get()
            ->groupBy(fn($policy) => $policy->created_at->format('Y-m'));

        // Build the ColumnChartModel
        $columnChartModel = (new ColumnChartModel())
            // ->setTitle('Pólizas Creadas (Últimos 6 Meses)')
            ->setAnimated(true)
            ->setLegendVisibility(false)
            ->setDataLabelsEnabled(true)
            ->setColumnWidth(30);

        // Add columns
        foreach ($months as $month) {
            $count = $policies->get($month)?->count() ?? 0;
            $monthLabel = Carbon::parse($month . '-01')->translatedFormat('M Y');
            $columnChartModel->addColumn($monthLabel, $count, '#3B82F6');
        }

        return view('livewire.home.charts.policies-by-month-chart', [
            'columnChartModel' => $columnChartModel,
        ]);
    }
}
