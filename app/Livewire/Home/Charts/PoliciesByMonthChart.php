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
            ->with('plan:id,name')
            ->where('status', 'Active')
            ->whereBetween('start_date', [$startDate, Carbon::today()])
            ->get()
            ->groupBy(fn ($policy) => $policy->plan->name);

        $planNames = $policies->keys()->sort()->values();
        $colors = [
            '#3B82F6',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#8B5CF6',
            '#06B6D4',
            '#EC4899',
            '#84CC16',
        ];

        $columnChartModel = (new ColumnChartModel())
            ->multiColumn()
            ->stacked()
            ->setAnimated(true)
            ->setLegendVisibility(true)
            ->setDataLabelsEnabled(true)
            ->setColumnWidth(30)
            ->setXAxisCategories($months->map(
                fn ($month) => Carbon::parse($month.'-01')->translatedFormat('M Y')
            )->all())
            ->setColors($planNames->map(
                fn ($planName, $index) => $colors[$index % count($colors)]
            )->all())
            ->setJsonConfig([
                'plotOptions.bar.dataLabels.total.enabled' => 'true',
            ]);

        foreach ($planNames as $planName) {
            $policiesByMonth = $policies->get($planName)
                ->groupBy(fn ($policy) => $policy->start_date->format('Y-m'));

            foreach ($months as $month) {
                $columnChartModel->addSeriesColumn(
                    $planName,
                    Carbon::parse($month.'-01')->translatedFormat('M Y'),
                    $policiesByMonth->get($month)?->count() ?? 0
                );
            }
        }

        return view('livewire.home.charts.policies-by-month-chart', [
            'columnChartModel' => $columnChartModel,
        ]);
    }
}
