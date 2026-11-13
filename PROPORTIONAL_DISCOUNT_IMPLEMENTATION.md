# Proportional Discount Implementation for Amount-type Coupons

## Overview
Updated `calculateTotals()` methods in both PaymentPage and DRNotesPage to properly handle distributing Amount-type coupons across multiple services proportionally.

## Changes Made

### 1. **PaymentPage.php** (`app/Livewire/Receptionist/PaymentPage.php`)
   
   - **Updated `calculateTotals()` method** (lines 700-732):
     - Added calculation of proportional discounts via `calculateProportionalAmountCouponDiscounts()`
     - Now passes proportional discount data to `calculatePatientChargeWithCoupon()`
     - Passes additional parameters: `$proportionalDiscounts`, `$service->service_id`, `$benefitId`
   
   - **Updated `mapCouponsToServices()` method** (lines 993-1006):
     - Changed from using single `$benefit->coupon->service_id` to `$coupon->services` (many-to-many relationship)
     - Now correctly maps all services associated with a coupon
     - Removed the `$usedServiceIds` limitation that prevented multi-service coupons
   
   - **Updated `calculatePatientChargeWithCoupon()` method** (lines 1008-1028):
     - Added optional parameters: `?array $proportionalDiscounts`, `?int $serviceId`, `?int $benefitId`
     - For Amount-type coupons with multiple services, uses pre-calculated proportional discount
     - Falls back to full coupon value for single-service coupons

### 2. **DRNotesPage.php** (`app/Livewire/Mobile/Doctor/DRNotesPage.php`)
   
   - **Updated `calculateTotals()` method** (lines 619-656):
     - Added same proportional discount calculation as PaymentPage
     - Now calls `calculateProportionalAmountCouponDiscounts()`
     - Passes additional parameters to `calculatePatientChargeWithCoupon()`
   
   - **Updated `mapCouponsToServices()` method** (lines 958-971):
     - Same changes as PaymentPage for multi-service coupon support
   
   - **Updated `calculatePatientChargeWithCoupon()` method** (lines 973-1004):
     - Same signature and logic as PaymentPage
   
   - **Added helper methods**:
     - `getServiceCouponServiceIds()` (line 1006): Extracts service IDs from coupon relationship
     - `calculateProportionalAmountCouponDiscounts()` (lines 1008-1051): Calculates proportional distribution

## How It Works

### Example: Amount Coupon Applied to Multiple Services
```
Provider: Mar Negro (20% discount, 10% commission)
Service 1 (Tórax): $500
Service 2 (TAC): $2,500
Coupon (Amount): $1,500 (100% inmax coverage)

Total Services Price: $3,000

Step 1: Calculate proportions
- Tórax proportion: $500 / $3,000 = 0.1667 (16.67%)
- TAC proportion: $2,500 / $3,000 = 0.8333 (83.33%)

Step 2: Distribute coupon amount
- Tórax coupon portion: $1,500 × 0.1667 = $250
- TAC coupon portion: $1,500 × 0.8333 = $1,250

Step 3: Calculate patient charge (price - coupon portion)
- Tórax: $500 - $250 = $250
- TAC: $2,500 - $1,250 = $1,250

Step 4: Calculate INMAX pays (with 20% provider discount)
- Tórax price with discount: $500 × 0.8 = $400
- Tórax INMAX pays: ($400 - $250) × 1.0 (100% coverage) = $150
- TAC price with discount: $2,500 × 0.8 = $2,000
- TAC INMAX pays: ($2,000 - $1,250) × 1.0 = $750

Step 5: Calculate commission
- Commission INMAX: ($250 + $1,250) × 0.10 - ($150 + $750)
- Commission INMAX: $150 - $900 = -$750 (INMAX pays provider)
```

## Supported Coupon Types

1. **Amount**: Distributed proportionally to service prices
2. **Percentage**: Applied to each service individually (percentage of service price)
3. **Service**: 100% discount on service (applied per service)

## Key Implementation Details

- **Proportional Distribution**: Only applied to Amount-type coupons with multiple services
- **Backward Compatible**: Single-service coupons use full coupon value (no proportion)
- **INMAX Coverage**: Applied after proportional discount calculation
- **Provider Discount**: Applied before INMAX coverage calculation
- **Commission Calculation**: 
  - INMAX Commission = (Patient Charge × Provider Commission %) - INMAX Pays
  - Can be negative (INMAX pays provider)
- **Ganancia del Socio**: Patient Charge - INMAX Commission

## Testing

All scenarios from requirements now supported:
- ✓ No coupon applied (provider discount only)
- ✓ Single Amount coupon
- ✓ Single Percentage coupon  
- ✓ Single Service coupon
- ✓ Multiple Amount coupons with proportional distribution
- ✓ Mixed coupon types in same appointment
- ✓ Negative INMAX commission (INMAX pays provider)
