<?php

namespace Tests\Feature;

use App\Livewire\Policies\PoliciesPage;
use App\Livewire\Policies\PolicyPreregistrationPage;
use App\Models\Plan;
use App\Models\PlanBenefit;
use App\Models\Policy;
use App\Models\PolicyPreregistration;
use App\Models\Service;
use App\Models\User;
use App\Models\UserPinSetupToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PolicyPreregistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_create_policy_preregistration_and_store_test_link(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Individual',
            'price' => 999.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $this->actingAs($salesUser);

        Livewire::test(PoliciesPage::class)
            ->set('preregistrationPhone', '3310000001')
            ->set('preregistrationPlan', (string) $plan->id)
            ->call('savePreregistration')
            ->assertHasNoErrors()
            ->assertSet('lastPreregistrationPhone', '3310000001')
            ->assertSet('lastPreregistrationPlanName', 'Plan Individual');

        $this->assertDatabaseHas('policy_preregistrations', [
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'parent_policy_id' => null,
            'phone' => '3310000001',
        ]);
    }

    public function test_preregistration_page_can_be_rendered_with_a_valid_token(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Familiar',
            'price' => 1200.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $token = 'policy-token-123';

        PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'phone' => '3310000002',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->get('/policy-registration/'.$token);

        $response->assertStatus(200);
        $response->assertSee('3310000002');
        $response->assertSee('Plan Familiar');
    }

    public function test_preregistration_page_creates_user_policy_and_redirects_to_pin_setup(): void
    {
        Storage::fake('public');

        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Integral',
            'price' => 1800.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $service = Service::query()->create([
            'name' => 'Consulta medica',
            'type' => 'Event',
        ]);

        PlanBenefit::query()->create([
            'plan_id' => $plan->id,
            'service_id' => $service->id,
            'events' => 3,
        ]);

        $token = 'policy-register-token';

        $preregistration = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'phone' => '3310000003',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        Livewire::test(PolicyPreregistrationPage::class, ['token' => $token])
            ->set('form.attachment', UploadedFile::fake()->image('cliente.jpg'))
            ->set('form.name', 'Cliente Demo')
            ->set('form.email', 'cliente.demo@example.com')
            ->set('form.birth', '1990-05-10')
            ->set('form.curp', 'DEMO900510HDFAAA01')
            ->set('form.insurance', ['imss'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::query()->where('phone', '3310000003')->first();

        $this->assertNotNull($user);

        $policy = Policy::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($policy);
        $this->assertSame($salesUser->id, $policy->sales_user_id);
        $this->assertSame($plan->id, $policy->plan_id);
        $this->assertSame($preregistration->id, $policy->policy_preregistration_id);
        $this->assertDatabaseHas('policy_services', [
            'policy_id' => $policy->id,
            'service_id' => $service->id,
            'included' => 3,
        ]);

        $preregistration->refresh();

        $this->assertNotNull($preregistration->used_at);
        $this->assertDatabaseHas('user_pin_setup_tokens', [
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, UserPinSetupToken::query()->where('user_id', $user->id)->count());
    }
}
