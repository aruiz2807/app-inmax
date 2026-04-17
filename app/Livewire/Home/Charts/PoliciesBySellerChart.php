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
        // Define the start date (6 months ago)
        $startDate = Carbon::now()->subMonths(6)->startOfDay();

        // Get policies grouped by sales_user_id for the last 6 months
        $policiesBySeller = Policy::query()
            ->selectRaw('sales_user_id, count(*) as total_policies')
            ->whereNotNull('sales_user_id')
            ->where('created_at', '>=', $startDate)
            ->groupBy('sales_user_id')
            ->having('total_policies', '>', 0) // Only include sellers with at least one policy
            ->with('sales_user') // Eager load the sales user to get their name
            ->get();

        $pieChartModel = (new PieChartModel())
            //->setTitle('Pólizas por Vendedor (Últimos 6 Meses)')
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
