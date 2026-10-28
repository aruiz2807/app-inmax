<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

class CommissionSummary
{
    public static function calculate(Collection $appointments, ?int $mgSpecialtyId): array
    {
        $subtotal = 0;
        $couponDiscount = 0;
        $membershipDiscount = 0;
        $userPayment = 0;
        $commission = 0;
        $total = 0;

        foreach ($appointments as $appointment) {
            $subtotal += $appointment->subtotal;
            $couponDiscount += $appointment->coupon_discount;
            $membershipDiscount += self::membershipDiscountFor($appointment);
            $userPayment += $appointment->user_payment;

            $commission += $appointment->commission;
            $total += $appointment->total;
            
        }

        return [
            'subtotal' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'membership_discount' => $membershipDiscount,
            'user_payment' => $userPayment,
            'commission' => $commission,
            'total' => $total,
            'mg_specialty_id' => $mgSpecialtyId,
        ];
    }

    public static function membershipDiscountFor(object $appointment): float
    {
        $discountPercent = (float) ($appointment->doctor?->discount ?? 0);
        $discountBase = max(0, (float) $appointment->subtotal - (float) $appointment->coupon_discount);

        return round($discountBase * ($discountPercent / 100), 2);
    }
}
