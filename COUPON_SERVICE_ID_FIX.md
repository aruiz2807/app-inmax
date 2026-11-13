# Coupon Service_ID Fix for Multi-Service Amount Coupons

## Problem
When saving a coupon of type "Amount" with multiple services:
1. It was incorrectly saving the first service ID in the `service_id` column
2. The relation should be stored ONLY in the `coupon_service` many-to-many table
3. This caused the coupon to only appear when ALL services were marked as completed

## Solution Overview
Three changes were made to fix this:

### 1. CouponsForm.php - Fix Coupon Creation/Update
**File:** `app/Livewire/Forms/CouponsForm.php`

**Changed Method:** `couponAttributes()`

**Before:**
```php
private function couponAttributes(): array
{
    return [
        'service_id' => $this->selectedServices()[0],  // ❌ Always saved first service
        // ...
    ];
}
```

**After:**
```php
private function couponAttributes(): array
{
    // For Amount type coupons with multiple services, service_id should be null
    // For other types, use the single selected service
    $serviceId = null;
    if ($this->type === 'Amount') {
        // Amount coupons use only the many-to-many relationship
        $serviceId = null;
    } else {
        // Percentage and Service type coupons use service_id
        $serviceId = $this->service;
    }

    return [
        'service_id' => $serviceId,
        // ...
    ];
}
```

**Impact:**
- Amount type coupons now save with `service_id = NULL`
- Percentage and Service type coupons continue to use `service_id`
- Multiple services are stored exclusively in the `coupon_service` many-to-many table

### 2. PaymentPage.php & DRNotesPage.php - Improve Coupon Availability Logic
**Files:** 
- `app/Livewire/Receptionist/PaymentPage.php`
- `app/Livewire/Mobile/Doctor/DRNotesPage.php`

**Changed Method:** `checkCouponAvailability()` - filter logic

**Before:**
The filter checked `service_id` and `services` separately, which could result in:
- Requiring all services to be completed for multi-service coupons
- Inconsistent behavior based on evaluation order

**After:**
```php
->filter(function ($benefit) {
    $coupon = $benefit->coupon;
    
    // Check if coupon has any service restrictions
    $hasServiceRestriction = $coupon->service_id || $coupon->services->isNotEmpty();
    
    // Universal coupon (no service restrictions at all)
    if (! $hasServiceRestriction) {
        return true;
    }

    // Coupon with multiple services (many-to-many relationship)
    // Priority: Check many-to-many first
    if ($coupon->services->isNotEmpty()) {
        foreach ($coupon->services as $couponService) {
            $service = $this->appointment->services->firstWhere('service_id', $couponService->id);
            if ($service && $this->isServiceCompleted($service)) {
                return true;
            }
        }
        // If has many-to-many services but none matched, return false
        return false;
    }

    // Coupon with single service (service_id) - only if no many-to-many services
    if ($coupon->service_id) {
        $service = $this->appointment->services->firstWhere('service_id', $coupon->service_id);
        return $service && $this->isServiceCompleted($service);
    }

    return false;
})
```

**Key improvements:**
1. **Priority:** Many-to-many relationship is checked first and prioritized
2. **Mutual Exclusivity:** If a coupon has many-to-many services, it will NOT check `service_id`
3. **Consistency:** Coupon appears if ANY of its services are completed
4. **Backward Compatible:** Single-service coupons with `service_id` still work

## Behavior Changes

### Before
**Scenario:** Coupon assigned to Nebulización + Electrocardiograma
- Nebulización completed, Electrocardiograma not → ❌ Coupon NOT visible
- Nebulización not, Electrocardiograma completed → ❌ Coupon NOT visible  
- Both completed → ✅ Coupon visible

### After
**Scenario:** Coupon assigned to Nebulización + Electrocardiograma
- Nebulización completed, Electrocardiograma not → ✅ Coupon visible
- Nebulización not, Electrocardiograma completed → ✅ Coupon visible
- Both completed → ✅ Coupon visible

## Migration Needed
If you have existing Amount-type coupons with `service_id` populated, you should run a migration to clean them up:

```php
// database/migrations/YYYY_MM_DD_HHMMSS_clear_service_id_for_amount_coupons.php
Schema::table('coupons', function (Blueprint $table) {
    // Set service_id to NULL for all Amount type coupons that have many-to-many services
    DB::statement("
        UPDATE coupons 
        SET service_id = NULL 
        WHERE type = 'Amount' 
        AND id IN (
            SELECT DISTINCT coupon_id FROM coupon_service
        )
    ");
});
```

## Files Modified
1. `app/Livewire/Forms/CouponsForm.php` - couponAttributes() method
2. `app/Livewire/Receptionist/PaymentPage.php` - checkCouponAvailability() filter logic
3. `app/Livewire/Mobile/Doctor/DRNotesPage.php` - checkCouponAvailability() filter logic

## Testing Checklist
- [ ] Create a new Amount coupon with 2+ services
- [ ] Verify coupon appears when only first service is completed
- [ ] Verify coupon appears when only second service is completed
- [ ] Verify coupon appears when all services are completed
- [ ] Verify Percentage coupon still works with single service
- [ ] Verify Service coupon still works with single service
- [ ] Verify universal coupon (no service) still works
- [ ] Verify existing single-service coupons still work
