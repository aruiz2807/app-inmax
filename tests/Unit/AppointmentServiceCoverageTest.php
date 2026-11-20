<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Coupon;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentServiceCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_coupon_badge_for_an_unpaid_service_with_a_coupon(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::BOOKED]);
        $service = AppointmentService::factory()->create([
            'appointment_id' => $appointment->id,
            'coupon_id' => 12,
        ]);

        $this->assertSame('Cupón', $service->covered_text);
        $this->assertSame('ticket', $service->covered_icon);
        $this->assertSame('blue', $service->covered_color);
    }

    public function test_it_shows_coupon_for_paid_service_with_applied_coupon_discount(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::COMPLETED]);
        $service = AppointmentService::factory()->create([
            'appointment_id' => $appointment->id,
            'coupon_id' => 12,
            'coupon_discount' => 10.00,
        ]);

        $this->assertSame('Cupón', $service->covered_text);
        $this->assertSame('ticket', $service->covered_icon);
        $this->assertSame('blue', $service->covered_color);
    }

    public function test_it_shows_additional_for_paid_service_with_coupon_but_no_discount(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::COMPLETED]);
        $service = AppointmentService::factory()->create([
            'appointment_id' => $appointment->id,
            'coupon_id' => 12,
            'coupon_discount' => 0,
        ]);

        $this->assertSame('Adicional', $service->covered_text);
        $this->assertSame('shield-exclamation', $service->covered_icon);
        $this->assertSame('yellow', $service->covered_color);
    }

    public function test_it_shows_additional_for_paid_service_with_null_coupon_discount(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::COMPLETED]);
        $service = AppointmentService::factory()->create([
            'appointment_id' => $appointment->id,
            'coupon_id' => 12,
            'coupon_discount' => null,
        ]);

        $this->assertSame('Adicional', $service->covered_text);
        $this->assertSame('shield-exclamation', $service->covered_icon);
        $this->assertSame('yellow', $service->covered_color);
    }
}
