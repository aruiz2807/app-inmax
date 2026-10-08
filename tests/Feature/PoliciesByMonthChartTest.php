<?php

namespace Tests\Feature;

use App\Livewire\Home\Charts\PoliciesByMonthChart;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoliciesByMonthChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stacks_active_monthly_memberships_by_plan(): void
    {
        Carbon::setTestNow('2026-10-08 12:00:00');

        try {
            $inmaxR = $this->createPlan('Inmax-R');
            $inmaxU = $this->createPlan('Inmax-U');

            foreach (range(1, 4) as $index) {
                $this->createPolicy($inmaxR, "R-$index", '2026-07-10');
            }

            foreach (range(1, 3) as $index) {
                $this->createPolicy($inmaxU, "U-$index", '2026-07-15');
            }

            $this->createPolicy($inmaxR, 'R-inactive', '2026-07-20', 'Inactive');
            $this->createPolicy($inmaxR, 'R-outside-range', '2026-04-20');

            $chart = (new PoliciesByMonthChart())->render()->getData()['columnChartModel']->toArray();
            $july = Carbon::parse('2026-07-01')->translatedFormat('M Y');

            $this->assertTrue($chart['isMultiColumn']);
            $this->assertTrue($chart['isStacked']);
            $this->assertTrue($chart['legend']['show']);
            $this->assertTrue($chart['dataLabels']['enabled']);
            $this->assertSame('true', $chart['jsonConfig']['plotOptions.bar.dataLabels.total.enabled']);
            $this->assertCount(2, array_unique($chart['colors']));
            $this->assertSame(6, count($chart['xAxis']['categories']));
            $this->assertSame(4, collect($chart['data']['Inmax-R'])->firstWhere('title', $july)['value']);
            $this->assertSame(3, collect($chart['data']['Inmax-U'])->firstWhere('title', $july)['value']);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createPlan(string $name): Plan
    {
        return Plan::query()->create([
            'name' => $name,
            'price' => 999,
            'type' => 'Individual',
            'status' => 'Active',
        ]);
    }

    private function createPolicy(Plan $plan, string $number, string $startDate, string $status = 'Active'): void
    {
        Policy::query()->create([
            'user_id' => User::factory()->create()->id,
            'plan_id' => $plan->id,
            'number' => $number,
            'type' => 'Individual',
            'start_date' => $startDate,
            'status' => $status,
        ]);
    }
}
