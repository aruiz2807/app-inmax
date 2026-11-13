<?php

namespace App\Livewire\Concerns;

use App\Models\Coupon;
use Illuminate\Support\Collection;

trait WithCouponSelection
{
    private function couponServiceIds(Coupon $coupon): array
    {
        $ids = $coupon->services->modelKeys();

        return $ids !== [] ? $ids : ($coupon->service_id ? [(int) $coupon->service_id] : []);
    }

    private function activeCouponServiceIds(Coupon $coupon): array
    {
        return array_values(array_filter(
            $this->couponServiceIds($coupon),
            fn ($id) => $this->hasCompletedCouponService((int) $id)
        ));
    }

    private function couponsOverlap(Coupon $first, Coupon $second): bool
    {
        $firstIds = $this->couponServiceIds($first);
        $secondIds = $this->couponServiceIds($second);

        return $firstIds === [] || $secondIds === []
            || array_intersect($this->activeCouponServiceIds($first), $this->activeCouponServiceIds($second)) !== [];
    }

    private function selectFirstCouponForService(int $serviceId): void
    {
        $benefit = $this->availableCoupons->first(
            fn ($benefit) => in_array($serviceId, $this->couponServiceIds($benefit->coupon), true)
        );

        if (! $benefit || in_array($benefit->id, $this->selectedCouponIds)) {
            return;
        }

        $this->selectedCouponIds = array_values(array_filter(
            $this->selectedCouponIds,
            function ($selectedId) use ($benefit) {
                $selected = $this->availableCoupons->firstWhere('id', (int) $selectedId);

                return $selected && ! $this->couponsOverlap($selected->coupon, $benefit->coupon);
            }
        ));
        $this->selectedCouponIds[] = $benefit->id;
        $this->normalizeSelectedCouponIds();
    }

    private function normalizeSelectedCouponIds(): void
    {
        $selected = collect();

        foreach ($this->selectedCouponIds as $id) {
            $benefit = $this->availableCoupons->firstWhere('id', (int) $id);
            if (! $benefit || $selected->contains('id', $benefit->id)) {
                continue;
            }

            if ($this->couponServiceIds($benefit->coupon) !== []
                && $this->activeCouponServiceIds($benefit->coupon) === []) {
                continue;
            }

            if ($selected->contains(fn ($other) => $this->couponsOverlap($other->coupon, $benefit->coupon))) {
                continue;
            }

            $selected->push($benefit);
        }

        $this->selectedCouponIds = $selected->pluck('id')->all();
    }

    private function selectedCouponBenefits(): Collection
    {
        $ids = array_map('intval', $this->selectedCouponIds);

        return $this->availableCoupons->filter(fn ($benefit) => in_array((int) $benefit->id, $ids, true)
            && ($this->couponServiceIds($benefit->coupon) === []
                || $this->activeCouponServiceIds($benefit->coupon) !== []))->values();
    }

    public function canSelectCoupon(int $benefitId): bool
    {
        $benefit = $this->availableCoupons->firstWhere('id', $benefitId);
        if (! $benefit || in_array($benefitId, $this->selectedCouponIds)) {
            return (bool) $benefit;
        }

        if ($this->couponServiceIds($benefit->coupon) !== []
            && $this->activeCouponServiceIds($benefit->coupon) === []) {
            return false;
        }

        return ! $this->selectedCouponBenefits()->contains(
            fn ($selected) => $this->couponsOverlap($benefit->coupon, $selected->coupon)
        );
    }

    private function hasSpecificCoupon(): bool
    {
        return $this->selectedCouponBenefits()->contains(
            fn ($benefit) => $this->couponServiceIds($benefit->coupon) !== []
        );
    }

    private function calculateProportionalCouponDiscounts(Collection $services, callable $priceForService): array
    {
        $benefits = $this->selectedCouponBenefits();
        $proportionalDiscounts = [];

        foreach ($benefits as $benefit) {
            $coupon = $benefit->coupon;
            
            if ($coupon->type !== 'Amount') {
                continue;
            }

            $couponServiceIds = $this->couponServiceIds($coupon);
            if (count($couponServiceIds) <= 1) {
                continue;
            }

            $applicableServices = $services->filter(fn ($service) => 
                $service->service_id && in_array((int) $service->service_id, $couponServiceIds)
            );

            if ($applicableServices->isEmpty()) {
                continue;
            }

            $totalPrice = $applicableServices->sum(fn ($service) => 
                max(0, round($priceForService($service), 2))
            );

            if ($totalPrice == 0) {
                continue;
            }

            foreach ($applicableServices as $service) {
                $servicePrice = max(0, round($priceForService($service), 2));
                $proportion = $servicePrice / $totalPrice;
                $proportionalDiscounts[$service->id] = round(
                    (float) $coupon->value * $proportion,
                    2
                );
            }
        }

        return $proportionalDiscounts;
    }

    private function couponFinancials(Collection $services, callable $priceForService, float $commissionRate, float $doctorDiscountRate): array
    {
        $benefits = $this->selectedCouponBenefits();
        $proportionalDiscounts = $this->calculateProportionalCouponDiscounts($services, $priceForService);
        $result = [];

        foreach ($services as $service) {
            $price = max(0, round($priceForService($service), 2));
            $benefit = $benefits->first(fn ($item) => $service->service_id
                && in_array((int) $service->service_id, $this->couponServiceIds($item->coupon)));
            $discount = 0;
            $coverage = 0;

            if ($benefit) {
                $coupon = $benefit->coupon;
                
                if ($coupon->type === 'Amount') {
                    $discount = $proportionalDiscounts[$service->id] ?? 0;
                } else {
                    $discount = match ($coupon->type) {
                        'Percentage' => min($price, round($price * (float) $coupon->value / 100, 2)),
                        'Service' => $price,
                        default => 0,
                    };
                }
                
                $coverage = round($discount * (float) $coupon->inmax_coverage_percent / 100, 2);
            }

            $patient = round($benefit
                ? max(0, $price - $discount)
                : $price * (1 - $doctorDiscountRate), 2);
            $commission = round($patient * $commissionRate - $coverage, 2);

            $result[$service->id] = [
                'coupon_id' => $discount > 0 ? $benefit->coupon_id : null,
                'subtotal' => $price,
                'coupon_discount' => $discount,
                'user_payment' => $patient,
                'commission' => $commission,
                'total' => $patient - $commission,
            ];
        }

        return $result;
    }
}
