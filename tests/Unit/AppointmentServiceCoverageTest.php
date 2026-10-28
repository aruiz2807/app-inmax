<?php

namespace Tests\Unit;

use App\Models\AppointmentService;
use PHPUnit\Framework\TestCase;

class AppointmentServiceCoverageTest extends TestCase
{
    public function test_it_shows_the_coupon_badge_for_a_service_with_a_redeemed_coupon(): void
    {
        $service = new AppointmentService(['coupon_id' => 12]);

        $this->assertSame('Cupón', $service->covered_text);
        $this->assertSame('ticket', $service->covered_icon);
        $this->assertSame('blue', $service->covered_color);
    }
}
