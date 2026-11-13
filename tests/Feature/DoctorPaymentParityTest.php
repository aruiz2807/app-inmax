<?php

namespace Tests\Feature;

use App\Livewire\Forms\DoctorNotesForm;
use App\Livewire\Mobile\Doctor\DRNotesPage;
use App\Livewire\Receptionist\PaymentPage;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Coupon;
use App\Models\Doctor;
use App\Models\Policy;
use App\Models\PolicyService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorPaymentParityTest extends TestCase
{
    public function test_totals_match_reception_for_each_coupon_type(): void
    {
        foreach ([
            ['Amount', 300, '1,150.00', '130.00', '1,020.00'],
            ['Percentage', 20, '1,250.00', '200.00', '1,050.00'],
            ['Service', 100, '450.00', '-360.00', '810.00'],
        ] as [$type, $value, $payment, $commission, $total]) {
            [$notes, $reception] = $this->pages(new NotesTotalsHarness, new PaymentTotalsHarness);
            $coupon = new Coupon([
                'service_id' => 1,
                'type' => $type,
                'value' => $value,
                'inmax_coverage_percent' => 50,
            ]);
            $benefit = new PolicyService;
            $benefit->id = 10;
            $benefit->setRelation('coupon', $coupon);
            $notes->availableCoupons = collect([$benefit]);
            $reception->availableCoupons = collect([$benefit]);
            $notes->selectedCouponIds = [10];
            $reception->selectedCouponIds = [10];

            (new \ReflectionMethod(DRNotesPage::class, 'calculateTotals'))->invoke($notes);
            (new \ReflectionMethod(PaymentPage::class, 'calculateTotals'))->invoke($reception);

            foreach ([$notes, $reception] as $page) {
                $this->assertSame('1,500.00', $page->subtotal);
                $this->assertSame($payment, $page->user_payment);
                $this->assertSame($commission, $page->commision);
                $this->assertSame($total, $page->total);
            }
        }
    }

    public function test_coupon_availability_uses_the_same_service_and_limit_filters(): void
    {
        [$notes, $reception] = $this->pages(new DRNotesPage, new PaymentPage);

        $notesQueries = DB::connection()->pretend(fn () => $notes->checkCouponAvailability());
        $receptionQueries = DB::connection()->pretend(fn () => $reception->checkCouponAvailability());

        $this->assertCount(1, $notesQueries);
        $this->assertSame($receptionQueries[0]['query'], $notesQueries[0]['query']);
        $this->assertSame($receptionQueries[0]['bindings'], $notesQueries[0]['bindings']);
        $this->assertStringContainsString('limit_min', $notesQueries[0]['query']);
        $this->assertStringContainsString('limit_max', $notesQueries[0]['query']);
    }

    public function test_coupon_selection_matches_reception(): void
    {
        [$notes, $reception] = $this->pages(new NotesTotalsHarness, new PaymentTotalsHarness);
        $benefits = collect();
        foreach ([10 => 1, 11 => 1, 12 => 2, 13 => null] as $benefitId => $serviceId) {
            $benefit = new PolicyService;
            $benefit->id = $benefitId;
            $benefit->setRelation('coupon', new Coupon(['service_id' => $serviceId]));
            $benefits->push($benefit);
        }

        foreach ([$notes, $reception] as $page) {
            $page->availableCoupons = $benefits;
            $method = new \ReflectionMethod($page instanceof DRNotesPage ? DRNotesPage::class : PaymentPage::class, 'selectFirstCouponForService');
            $method->invoke($page, 1);
            $this->assertSame([10], $page->selectedCouponIds);
            $this->assertFalse($page->canSelectCoupon(11));
            $this->assertFalse($page->canSelectCoupon(13));
            $method->invoke($page, 2);
            $this->assertSame([10, 12], $page->selectedCouponIds);
        }
    }

    private function pages(DRNotesPage $notes, PaymentPage $reception): array
    {
        $doctor = (new Doctor(['discount' => 10, 'commission' => 20]))->forceFill(['id' => 1]);
        $policy = (new Policy(['type' => 'Individual']))->forceFill(['id' => 5]);
        $patient = (new User)->setRelation('policy', $policy);
        $doctorUser = (new User)->setRelation('doctor', $doctor);
        $services = new EloquentCollection([
            (new AppointmentService(['service_id' => 1, 'subtotal' => 1000]))->forceFill(['id' => 11]),
            (new AppointmentService(['service_id' => 2, 'subtotal' => 500]))->forceFill(['id' => 12]),
        ]);
        $appointment = (new Appointment)->forceFill(['doctor_id' => 1]);
        $appointment->setRelation('doctor', $doctor);
        $appointment->setRelation('user', $patient);
        $appointment->setRelation('services', $services);

        $notes->appointment = $appointment;
        $notes->user = $doctorUser;
        $notes->services = $services;
        $notes->form = new DoctorNotesForm($notes, 'form');
        $notes->form->services = [11 => true, 12 => true];
        $notes->form->servicePrices = [11 => '2,000.00', 12 => '500.00'];
        $notes->subtotal = '1,500.00';
        $notes->availableCoupons = collect();

        $reception->appointment = $appointment;
        $reception->canManageServices = true;
        $reception->servicesToComplete = [11 => true, 12 => true];
        $reception->servicePrices = [11 => '2,000.00', 12 => '500.00'];
        $reception->subtotal = '1,500.00';
        $reception->availableCoupons = collect();

        return [$notes, $reception];
    }
}

class NotesTotalsHarness extends DRNotesPage
{
    public function checkCouponAvailability() {}
}

class PaymentTotalsHarness extends PaymentPage
{
    public function checkCouponAvailability(): void {}
}
