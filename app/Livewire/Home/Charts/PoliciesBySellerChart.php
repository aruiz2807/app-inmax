<?php

namespace App\Livewire\Home\Charts;

use App\Models\Policy;
use Asantibanez\LivewireCharts\Models\PieChartModel;
use Carbon\Carbon;
use Livewire\Component;

class PoliciesBySellerChart extends Component
{
    public function render()
    {
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();

        $policiesBySeller = Policy::query()
            ->selectRaw('sales_user_id, count(*) as total_policies')
            ->whereNotNull('sales_user_id')
            ->where('status', 'Active')
            ->whereBetween('start_date', [$startDate, Carbon::today()])
            ->groupBy('sales_user_id')
            ->having('total_policies', '>', 0)
            ->with('sales_user')
            ->get();

        $pieChartModel = (new PieChartModel())
            ->setAnimated(true)
            ->setLegendVisibility(true)
            ->setDataLabelsEnabled(true);

        $colors = [
            '#3B82F6', // blue-500
            '#2563EB', // blue-600
            '#1D4ED8', // blue-700
            '#1E40AF', // blue-800
            '#172554', // blue-900
        ];
        $colorIndex = 0;

        foreach ($policiesBySeller as $item) {
            $sellerName = $item->sales_user ? $item->sales_user->name : 'Vendedor Desconocido';
            $pieChartModel->addSlice(
                $sellerName,
                $item->total_policies,
                $colors[$colorIndex % count($colors)]
            );
            $colorIndex++;
        }

        return view('livewire.home.charts.policies-by-seller-chart', [
            'pieChartModel' => $pieChartModel,
        ]);
    }
}
