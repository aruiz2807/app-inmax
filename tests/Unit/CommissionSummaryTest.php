<?php

namespace Tests\Unit;

use App\Services\Reports\CommissionSummary;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CommissionSummaryTest extends TestCase
{
    public function test_it_calculates_the_commission_totals_and_inverts_mg_amounts(): void
    {
        $appointments = new Collection([
            (object) [
                'subtotal' => 100,
                'coupon_discount' => 10,
                'user_payment' => 90,
                'commission' => 15,
                'total' => 75,
                'doctor' => (object) ['specialty_id' => 1],
            ],
            (object) [
                'subtotal' => 200,
                'coupon_discount' => 20,
                'user_payment' => 180,
                'commission' => 30,
                'total' => 150,
                'doctor' => (object) ['specialty_id' => 2],
            ],
        ]);

        $totals = CommissionSummary::calculate($appointments, 2);

        $this->assertSame([
            'subtotal' => 300,
            'coupon_discount' => 30,
            'membership_discount' => 0.0,
            'user_payment' => 270,
            'commission' => -135,
            'total' => 75,
            'mg_specialty_id' => 2,
        ], $totals);
    }

    public function test_it_sums_membership_discounts_from_each_doctor_configuration(): void
    {
        $appointments = new Collection([
            (object) [
                'subtotal' => 800,
                'coupon_discount' => 0,
                'user_payment' => 672,
                'commission' => 0,
                'total' => 0,
                'doctor' => (object) ['discount' => 16],
            ],
            (object) [
                'subtotal' => 1000,
                'coupon_discount' => 100,
                'user_payment' => 810,
                'commission' => 0,
                'total' => 0,
                'doctor' => (object) ['discount' => 10],
            ],
        ]);

        $totals = CommissionSummary::calculate($appointments, null);

        $this->assertSame(128.0, CommissionSummary::membershipDiscountFor($appointments[0]));
        $this->assertSame(218.0, $totals['membership_discount']);
    }
}
