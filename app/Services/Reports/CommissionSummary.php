<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

class CommissionSummary
{
    public static function calculate(Collection $appointments, ?int $mgSpecialtyId): array
    {
        $subtotal = 0;
        $couponDiscount = 0;
        $userPayment = 0;
        $commission = 0;
        $total = 0;

        foreach ($appointments as $appointment) {
            $subtotal += $appointment->subtotal;
            $couponDiscount += $appointment->coupon_discount;
            $userPayment += $appointment->user_payment;

            if ($appointment->doctor->specialty_id === $mgSpecialtyId) {
                $commission += -$appointment->total;
            } else {
                $commission += $appointment->commission;
                $total += $appointment->total;
            }
        }

        return [
            'subtotal' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'user_payment' => $userPayment,
            'commission' => $commission,
            'total' => $total,
            'mg_specialty_id' => $mgSpecialtyId,
        ];
    }
}
