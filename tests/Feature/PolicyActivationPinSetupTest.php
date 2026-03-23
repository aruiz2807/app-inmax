<?php

namespace Tests\Feature;

use App\Livewire\Policies\PoliciesPage;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\User;
use App\Models\UserPinSetupToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PolicyActivationPinSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_activate_membership_and_generate_pin_setup_link(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $member = User::factory()->create([
            'profile' => 'User',
            'phone' => '3310000099',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Activable',
            'price' => 999.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $policy = Policy::query()->create([
            'user_id' => $member->id,
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'number' => 'POL-1001',
            'type' => 'Individual',
            'status' => 'Inactive',
        ]);

        $this->actingAs($salesUser);

        $component = Livewire::test(PoliciesPage::class)
            ->set('policyId', $policy->id)
            ->call('confirmActivation')
            ->assertHasNoErrors()
            ->assertSet('lastPinSetupName', $member->name)
            ->assertSet('lastPinSetupPhone', '3310000099');

        $policy->refresh();

        $this->assertSame('Active', $policy->status);
        $this->assertNotNull($policy->start_date);
        $this->assertNotNull($policy->end_date);
        $this->assertSame(1, UserPinSetupToken::query()->where('user_id', $member->id)->count());
        $this->assertNotEmpty($component->get('lastPinSetupUrl'));
        $this->assertStringContainsString('/pin/setup/', (string) $component->get('lastPinSetupUrl'));
    }
}
