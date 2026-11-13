# Coupon Availability & Service Mapping Fix - Multi-Service Amount Coupons

## Problem
When a coupon of type Amount (or other types) is assigned to multiple services and the user marks a service as "completed" (realizado), the coupon was not appearing in the available coupons list, even though the service was included in the coupon's services.

## Root Cause
Multiple places in the code were only checking the `coupon->service_id` field (single service relationship), but were not considering coupons with multiple services that are stored in the `coupon_service` many-to-many table through the `coupon->services()` relationship.

## Solution
Updated all coupon-related logic in both PaymentPage and DRNotesPage controllers to properly handle coupons with multiple services.

### Files Modified
- `app/Livewire/Receptionist/PaymentPage.php`
- `app/Livewire/Mobile/Doctor/DRNotesPage.php`

### Changes Made

#### 1. `checkCouponAvailability()` Method
**Updated the query and filter logic to check both single and multi-service coupons**

##### Query Update
```php
// Added multi-service coupon check in whereHas
->where(function ($serviceQuery) use ($serviceIds) {
    $serviceQuery->whereNull('service_id')
        ->orWhereIn('service_id', $serviceIds)
        ->orWhereHas('services', function ($sq) use ($serviceIds) {
            $sq->whereIn('services.id', $serviceIds);
        });
})
```

##### Filter Update
```php
->filter(function ($benefit) {
    $coupon = $benefit->coupon;
    
    // Universal coupon (no service restrictions)
    if (! $coupon->service_id && $coupon->services->isEmpty()) {
        return true;
    }

    // Coupon with single service (service_id)
    if ($coupon->service_id) {
        $service = $this->appointment->services->firstWhere('service_id', $coupon->service_id);
        return $service && $this->isServiceCompleted($service);
    }

    // Coupon with multiple services (many-to-many relationship)
    if ($coupon->services->isNotEmpty()) {
        foreach ($coupon->services as $couponService) {
            $service = $this->appointment->services->firstWhere('service_id', $couponService->id);
            if ($service && $this->isServiceCompleted($service)) {
                return true;
            }
        }
    }

    return false;
})
```

#### 2. `updated()` Method (Service Completion Handler)
**Updated to properly handle coupon removal when a service is unchecked**

When a service is marked as incomplete, now properly distinguishes between:
- Single-service coupons: Removed if they only apply to the unchecked service
- Multi-service coupons: Kept because they still apply to other services

```php
// Remove coupon if it only applies to this service (single-service coupon)
if ($coupon->service_id && (int) $coupon->service_id === (int) $service->service_id) {
    return false;
}

// Keep coupon if it applies to multiple services (many-to-many)
if ($coupon->services->isNotEmpty()) {
    return true;
}
```

#### 3. `selectFirstCouponForService()` Method
**Updated to find and select appropriate coupons for a service**

Now checks both single-service and multi-service coupons:
```php
// Find a coupon that applies to this service (single-service or multi-service)
$benefit = $this->availableCoupons->first(
    function ($benefit) use ($serviceId) {
        $coupon = $benefit->coupon;
        
        // Single-service coupon with exact match
        if ($coupon->service_id && (int) $coupon->service_id === $serviceId) {
            return true;
        }
        
        // Multi-service coupon (many-to-many relationship)
        if ($coupon->services->isNotEmpty()) {
            return $coupon->services->contains(fn ($s) => (int) $s->id === $serviceId);
        }
        
        return false;
    }
);
```

#### 4. `persistServicesFinancials()` Method (PaymentPage only)
**Updated financial calculation for coupons**

Fixed the service coupon discount calculation to check both single and multi-service coupons:
```php
// Check if coupon applies to this service
$appliesToService = false;

if ($coupon->service_id && (int) $coupon->service_id === (int) $service->service_id) {
    $appliesToService = true;
} elseif ($coupon->services->isNotEmpty()) {
    $appliesToService = $coupon->services->contains(fn ($s) => (int) $s->id === (int) $service->service_id);
}

if (! $appliesToService || $coupon->type === 'Service') {
    return 0;
}
```

#### 5. `persistServiceFinancials()` Method (DRNotesPage only)
**Same update as PaymentPage for financial calculations**

## Test Scenario

**Before Fix:**
- Service 1 (Tórax): marked as completed
- Service 2 (TAC): NOT marked as completed
- Coupon: Amount $1,500 assigned to both Service 1 AND Service 2
- Result: ❌ Coupon does NOT appear in available coupons

**After Fix:**
- Service 1 (Tórax): marked as completed
- Service 2 (TAC): NOT marked as completed
- Coupon: Amount $1,500 assigned to both Service 1 AND Service 2
- Result: ✅ Coupon DOES appear in available coupons (because Service 1 is completed)

**Additional Test Scenario:**
- Both services completed
- User unchecks Service 2
- Multi-service coupon should remain in selectedCouponIds
- Result: ✅ Coupon remains selected (still applies to Service 1)

## Backward Compatibility
This change is fully backward compatible:
- ✅ Single-service coupons continue to work as before
- ✅ Universal coupons continue to work as before
- ✅ New multi-service coupons now work correctly
- ✅ All existing financial calculations remain unchanged

