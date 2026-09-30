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

        $startDate = Carbon::now()->subMonths(5)->startOfMonth();

        $policies = Policy::query()
            ->where('status', 'Active')
            ->whereBetween('start_date', [$startDate, Carbon::today()])
            ->get()
            ->groupBy(fn($policy) => $policy->start_date->format('Y-m'));

        $columnChartModel = (new ColumnChartModel())
            ->setAnimated(true)
            ->setLegendVisibility(false)
            ->setDataLabelsEnabled(true)
            ->setColumnWidth(30);

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
