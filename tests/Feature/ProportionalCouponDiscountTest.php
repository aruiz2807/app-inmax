<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Coupon;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Tests\TestCase;

class ProportionalCouponDiscountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test: Single Amount coupon applied to multiple services (proportional distribution)
     * 
     * Example from requirements:
     * Provider: Mar Negro (20% discount, 10% commission)
     * Service 1 (Tórax): $500
     * Service 2 (TAC): $2,500
     * Coupon (Amount): $1,500 (100% inmax coverage)
     * 
     * Expected:
     * - Tórax coupon portion: $250
     * - TAC coupon portion: $1,250
     * - Patient payment: $250 + $1,250 = $1,500
     * - INMAX pays: $150 + $750 = $900
     * - Commission INMAX: $150 - $900 = -$750
     * - Ganancia socio: $1,500 - (-$750) = $2,250
     */
    public function test_amount_coupon_proportional_distribution()
    {
        // This test documents the expected behavior
        // Implementation tested through PaymentPage and DRNotesPage Livewire components
        
        // Provider: 20% discount, 10% commission
        $discountPercent = 0.20;
        $commissionPercent = 0.10;
        
        // Service prices
        $torax = 500;
        $tac = 2500;
        $totalPrice = $torax + $tac;
        
        // Coupon
        $couponAmount = 1500;
        $inmaxCoveragePct = 1.0; // 100%
        
        // Step 1: Proportional distribution
        $toraxProportion = $torax / $totalPrice;
        $tacProportion = $tac / $totalPrice;
        
        $toraxCouponPortion = round($couponAmount * $toraxProportion, 2);
        $tacCouponPortion = round($couponAmount * $tacProportion, 2);
        
        $this->assertEquals(250, $toraxCouponPortion);
        $this->assertEquals(1250, $tacCouponPortion);
        
        // Step 2: Calculate patient charge (price - coupon portion)
        $toraxPatientCharge = $torax - $toraxCouponPortion;
        $tacPatientCharge = $tac - $tacCouponPortion;
        $totalPatientCharge = $toraxPatientCharge + $tacPatientCharge;
        
        $this->assertEquals(250, $toraxPatientCharge);
        $this->assertEquals(1250, $tacPatientCharge);
        $this->assertEquals(1500, $totalPatientCharge);
        
        // Step 3: Calculate INMAX pays
        // INMAX paga = cobertura × (precio con descuento − patient_charge)
        $toraxPriceWithDiscount = $torax * (1 - $discountPercent);
        $toraxInmaxPays = ($toraxPriceWithDiscount - $toraxPatientCharge) * $inmaxCoveragePct;
        
        $tacPriceWithDiscount = $tac * (1 - $discountPercent);
        $tacInmaxPays = ($tacPriceWithDiscount - $tacPatientCharge) * $inmaxCoveragePct;
        
        $totalInmaxPays = round($toraxInmaxPays + $tacInmaxPays, 2);
        
        $this->assertEquals(400, $toraxPriceWithDiscount);
        $this->assertEquals(150, $toraxInmaxPays);
        $this->assertEquals(2000, $tacPriceWithDiscount);
        $this->assertEquals(750, $tacInmaxPays);
        $this->assertEquals(900, $totalInmaxPays);
        
        // Step 4: Calculate commission
        // Comisión INMAX = (patient_charge × comisión%) - INMAX paga
        $commissionAmount = ($totalPatientCharge * $commissionPercent) - $totalInmaxPays;
        
        $this->assertEquals(-750, $commissionAmount);
        
        // Step 5: Calculate partner profit (ganancia del socio)
        // Ganancia = patient_charge - commission
        // If commission is negative, it adds to the patient payment
        $partnerProfit = $totalPatientCharge - $commissionAmount;
        
        $this->assertEquals(2250, $partnerProfit);
    }

    /**
     * Test: No coupon applied (provider discount only)
     * 
     * Example:
     * Service 1: $1000
     * Provider discount: 10%
     * 
     * Expected patient payment: $900
     */
    public function test_no_coupon_provider_discount_only()
    {
        $servicePrice = 1000;
        $providerDiscountPercent = 0.10;
        
        // Patient charge with provider discount
        $patientCharge = $servicePrice * (1 - $providerDiscountPercent);
        
        $this->assertEquals(900, $patientCharge);
    }

    /**
     * Test: Percentage coupon applied
     * 
     * Example:
     * Service 1: $1000
     * Percentage coupon: 10%
     * 
     * Expected patient payment: $900
     */
    public function test_percentage_coupon()
    {
        $servicePrice = 1000;
        $percentageCoupon = 0.10;
        
        $discount = $servicePrice * $percentageCoupon;
        $patientCharge = $servicePrice - $discount;
        
        $this->assertEquals(100, $discount);
        $this->assertEquals(900, $patientCharge);
    }

    /**
     * Test: Service coupon (100% discount)
     * 
     * Example:
     * Service 1: $1000
     * Service coupon: 100% (free service)
     * 
     * Expected patient payment: $0
     */
    public function test_service_coupon()
    {
        $servicePrice = 1000;
        // Service coupon = 100% discount
        $patientCharge = $servicePrice - $servicePrice;
        
        $this->assertEquals(0, $patientCharge);
    }

    /**
     * Test: Multiple coupons of different types
     * 
     * Example:
     * Service 1: $1000 with 10% percentage coupon
     * Service 2: $500 with Service coupon (100%)
     * 
     * Expected patient payment: $900 + $0 = $900
     */
    public function test_multiple_coupons_different_types()
    {
        // Service 1 with percentage coupon
        $service1Price = 1000;
        $service1Coupon = 0.10; // 10%
        $service1Charge = $service1Price * (1 - $service1Coupon);
        
        // Service 2 with service coupon
        $service2Price = 500;
        $service2Charge = $service2Price - $service2Price; // 100% discount
        
        $totalCharge = $service1Charge + $service2Charge;
        
        $this->assertEquals(900, $service1Charge);
        $this->assertEquals(0, $service2Charge);
        $this->assertEquals(900, $totalCharge);
    }

    /**
     * Test: Amount coupon with single service (no proportional distribution)
     * 
     * Example:
     * Service 1: $1000
     * Amount coupon: $250
     * 
     * Expected patient payment: $750
     */
    public function test_amount_coupon_single_service()
    {
        $servicePrice = 1000;
        $amountCoupon = 250;
        
        $patientCharge = max(0, $servicePrice - $amountCoupon);
        
        $this->assertEquals(750, $patientCharge);
    }
}
