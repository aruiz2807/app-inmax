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
            'user_payment' => 270,
            'commission' => -135,
            'total' => 75,
            'mg_specialty_id' => 2,
        ], $totals);
    }
}
