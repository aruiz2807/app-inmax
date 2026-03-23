<?php

namespace Tests\Feature;

use App\Livewire\Policies\PolicyPreregistrationPage;
use App\Livewire\Policies\PolicyPreregistrationsPage;
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

        Livewire::test(PolicyPreregistrationsPage::class)
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

    public function test_admin_can_create_preregistration_assigning_a_sales_promoter(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
        ]);

        $salesUser = User::factory()->create([
            'profile' => 'Sales',
            'name' => 'Promotor Uno',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Admin',
            'price' => 1099.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->set('preregistrationPhone', '3310000091')
            ->set('preregistrationPlan', (string) $plan->id)
            ->set('preregistrationSalesUser', (string) $salesUser->id)
            ->call('savePreregistration')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('policy_preregistrations', [
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'phone' => '3310000091',
        ]);
    }

    public function test_cannot_create_preregistration_with_existing_preregistration_phone(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Unico',
            'price' => 999.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'phone' => '3310000093',
            'token_hash' => hash('sha256', 'existing-prereg-phone'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($salesUser);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->set('preregistrationPhone', '3310000093')
            ->set('preregistrationPlan', (string) $plan->id)
            ->call('savePreregistration')
            ->assertHasErrors(['preregistrationPhone']);
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

    public function test_sales_user_can_edit_preregistration_and_rotate_link(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $originalPlan = Plan::query()->create([
            'name' => 'Plan Base',
            'price' => 900.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $updatedPlan = Plan::query()->create([
            'name' => 'Plan Plus',
            'price' => 1500.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $preregistration = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $originalPlan->id,
            'phone' => '3310000010',
            'token_hash' => hash('sha256', 'old-prereg-token'),
            'expires_at' => now()->addDay(),
        ]);

        $previousTokenHash = $preregistration->token_hash;

        $this->actingAs($salesUser);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->call('editPreregistration', $preregistration->id)
            ->assertSet('preregistrationPhone', '3310000010')
            ->set('preregistrationPhone', '3310000011')
            ->set('preregistrationPlan', (string) $updatedPlan->id)
            ->call('savePreregistration')
            ->assertHasNoErrors()
            ->assertSet('lastPreregistrationPhone', '3310000011')
            ->assertSet('lastPreregistrationPlanName', 'Plan Plus');

        $preregistration->refresh();

        $this->assertSame('3310000011', $preregistration->phone);
        $this->assertSame($updatedPlan->id, $preregistration->plan_id);
        $this->assertNotSame($previousTokenHash, $preregistration->token_hash);
    }

    public function test_admin_can_change_preregistration_promoter_when_editing(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
        ]);

        $salesUserOne = User::factory()->create([
            'profile' => 'Sales',
            'name' => 'Promotor Uno',
        ]);

        $salesUserTwo = User::factory()->create([
            'profile' => 'Sales',
            'name' => 'Promotor Dos',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Editable',
            'price' => 950.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $preregistration = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUserOne->id,
            'plan_id' => $plan->id,
            'phone' => '3310000092',
            'token_hash' => hash('sha256', 'admin-edit-token'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->call('editPreregistration', $preregistration->id)
            ->set('preregistrationSalesUser', (string) $salesUserTwo->id)
            ->call('savePreregistration')
            ->assertHasNoErrors();

        $preregistration->refresh();

        $this->assertSame($salesUserTwo->id, $preregistration->sales_user_id);
    }

    public function test_cannot_edit_preregistration_to_use_existing_phone_from_another_preregistration(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
        ]);

        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Sin Duplicados',
            'price' => 950.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $first = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'phone' => '3310000094',
            'token_hash' => hash('sha256', 'first-prereg'),
            'expires_at' => now()->addDay(),
        ]);

        $second = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'phone' => '3310000095',
            'token_hash' => hash('sha256', 'second-prereg'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->call('editPreregistration', $second->id)
            ->set('preregistrationPhone', '3310000094')
            ->call('savePreregistration')
            ->assertHasErrors(['preregistrationPhone']);

        $first->refresh();
        $second->refresh();

        $this->assertSame('3310000094', $first->phone);
        $this->assertSame('3310000095', $second->phone);
    }

    public function test_preregistration_page_creates_user_policy_and_shows_pending_activation_message(): void
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
            ->assertSet('registrationCompleted', true)
            ->assertSet('registeredMemberName', 'Cliente Demo')
            ->assertSee('Tus datos han sido registrados correctamente en el sistema de INMAX.')
            ->assertSee('Ver en Google Maps');

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
        $this->assertSame(0, UserPinSetupToken::query()->where('user_id', $user->id)->count());
    }

    public function test_sales_user_can_cancel_preregistration_and_public_link_shows_cancelled_message(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Activo',
            'price' => 1100.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $token = 'cancel-prereg-token';

        $preregistration = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'phone' => '3310000012',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($salesUser);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->call('promptPreregistrationCancellation', $preregistration->id)
            ->call('cancelPreregistration')
            ->assertHasNoErrors();

        $preregistration->refresh();

        $this->assertNotNull($preregistration->cancelled_at);
        $this->assertSame($salesUser->id, $preregistration->cancelled_by);

        auth()->logout();

        $response = $this->get('/policy-registration/'.$token);

        $response->assertStatus(200);
        $response->assertSee('Esta invitacion fue cancelada. Solicita una nueva al promotor.');
    }
}
