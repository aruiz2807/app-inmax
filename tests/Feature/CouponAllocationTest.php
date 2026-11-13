<?php

namespace Tests\Feature;

use App\Livewire\Concerns\WithCouponSelection;
use App\Models\Coupon;
use App\Models\PolicyService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CouponAllocationTest extends TestCase
{
    public function test_amount_is_spent_once_across_services_in_appointment_order(): void
    {
        $coupon = new Coupon(['type' => 'Amount', 'value' => 1500, 'inmax_coverage_percent' => 30]);
        $coupon->id = 10;
        $coupon->setRelation('services', new EloquentCollection(array_map(function ($id) {
            $service = new Service;
            $service->id = $id;

            return $service;
        }, [1, 2, 3, 4])));
        $benefit = new PolicyService;
        $benefit->id = 20;
        $benefit->coupon_id = 10;
        $benefit->setRelation('coupon', $coupon);
        $services = collect([
            (object) ['id' => 11, 'service_id' => 1, 'price' => 1000],
            (object) ['id' => 12, 'service_id' => 2, 'price' => 500],
            (object) ['id' => 13, 'service_id' => 3, 'price' => 500],
            (object) ['id' => 14, 'service_id' => 4, 'price' => 100],
        ]);
        $harness = new CouponSelectionHarness(collect([$benefit]), [20], [1, 2, 3, 4]);

        $financials = $harness->financials($services);

        $this->assertEquals([1000, 500, 0, 0], array_column($financials, 'coupon_discount'));
        $this->assertEquals([0, 0, 500, 100], array_column($financials, 'user_payment'));
        $this->assertSame([10, 10, null, null], array_column($financials, 'coupon_id'));
        $this->assertSame(-300.0, $financials[11]['commission']);
        $this->assertSame(-150.0, $financials[12]['commission']);
    }

    public function test_amount_is_capped_when_a_service_exceeds_the_remaining_balance(): void
    {
        $coupon = new Coupon(['type' => 'Amount', 'value' => 1500, 'inmax_coverage_percent' => 50]);
        $coupon->id = 10;
        $coupon->setRelation('services', new EloquentCollection(array_map(function ($id) {
            $service = new Service;
            $service->id = $id;

            return $service;
        }, [1, 2])));
        $benefit = new PolicyService;
        $benefit->id = 20;
        $benefit->coupon_id = 10;
        $benefit->setRelation('coupon', $coupon);
        $harness = new CouponSelectionHarness(collect([$benefit]), [20], [1, 2]);
        $financials = $harness->financials(collect([
            (object) ['id' => 11, 'service_id' => 1, 'price' => 1000],
            (object) ['id' => 12, 'service_id' => 2, 'price' => 1000],
        ]));

        $this->assertSame(500.0, $financials[12]['coupon_discount']);
        $this->assertSame(500.0, $financials[12]['user_payment']);
        $this->assertSame(-250.0, $financials[12]['commission']);
    }

    public function test_selection_conflicts_on_any_completed_service(): void
    {
        $service = new Service;
        $service->id = 2;
        $first = new Coupon(['type' => 'Amount']);
        $first->setRelation('services', new EloquentCollection([$service]));
        $second = new Coupon(['type' => 'Percentage']);
        $second->setRelation('services', new EloquentCollection([$service]));
        $firstBenefit = new PolicyService;
        $firstBenefit->id = 20;
        $firstBenefit->setRelation('coupon', $first);
        $secondBenefit = new PolicyService;
        $secondBenefit->id = 21;
        $secondBenefit->setRelation('coupon', $second);

        $harness = new CouponSelectionHarness(collect([$firstBenefit, $secondBenefit]), [20], [2]);

        $this->assertFalse($harness->canSelectCoupon(21));
        $this->assertTrue($harness->canSelectCoupon(20));
    }
}

class CouponSelectionHarness
{
    use WithCouponSelection;

    public function __construct(
        public Collection $availableCoupons,
        public array $selectedCouponIds,
        private array $completedServiceIds,
    ) {}

    private function hasCompletedCouponService(int $serviceId): bool
    {
        return in_array($serviceId, $this->completedServiceIds, true);
    }

    public function financials(Collection $services): array
    {
        return $this->couponFinancials($services, fn ($service) => $service->price, 0, 0);
    }
}
