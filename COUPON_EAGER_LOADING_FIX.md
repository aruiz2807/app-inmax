# Coupon Services Eager Loading Fix

## Problem
When a coupon is assigned to multiple services (e.g., Nebulización and Electrocardiograma), the coupon was not appearing consistently. Specifically:
- When marking Nebulización as completed: ❌ Coupon does NOT appear
- When marking Electrocardiograma as completed: ✅ Coupon DOES appear

This inconsistent behavior indicated a problem with how the coupon's services were being loaded.

## Root Cause
The `checkCouponAvailability()` method was using eager loading with only `->with('coupon')`, which loads the coupon model but NOT the related services (the many-to-many relationship).

When the filter logic checked `$coupon->services->isEmpty()`, it was performing a lazy load of the services. However, since the filter was iterating through services and Laravel's lazy loading was being triggered multiple times, the behavior was inconsistent and dependent on the order of iteration.

## Solution
Updated the eager loading in both PaymentPage and DRNotesPage to explicitly load the coupon's services relationship:

**Before:**
```php
$this->availableCoupons = PolicyService::with('coupon')
    ->where('policy_id', $policyId)
    // ... rest of query
```

**After:**
```php
$this->availableCoupons = PolicyService::with('coupon.services')
    ->where('policy_id', $policyId)
    // ... rest of query
```

The dot notation `coupon.services` tells Eloquent to eager load:
1. The `coupon` relationship on the PolicyService model
2. The `services` relationship on the Coupon model (many-to-many)

This ensures that when the filter logic checks `$coupon->services->isEmpty()` or iterates through `$coupon->services`, the collection is already loaded in memory and consistent.

## Files Modified
- `app/Livewire/Receptionist/PaymentPage.php` - Line ~534
- `app/Livewire/Mobile/Doctor/DRNotesPage.php` - Line ~265

## Result
**Now:**
- When marking Nebulización as completed: ✅ Coupon DOES appear
- When marking Electrocardiograma as completed: ✅ Coupon DOES appear
- When marking both as completed: ✅ Coupon DOES appear
- The coupon availability is now consistent regardless of which service is marked as completed first

## Benefits
1. **Consistency**: Coupon availability is now consistent regardless of service completion order
2. **Performance**: Single query with eager loading instead of multiple lazy loads
3. **Reliability**: All services relationships are pre-loaded and guaranteed to be available
