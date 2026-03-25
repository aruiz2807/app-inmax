<?php

namespace Tests\Feature;

use App\Livewire\Policies\IndividualPolicyPage;
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

    public function test_admin_can_create_group_member_preregistration_using_collective_policy_capacity(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
        ]);

        $salesUser = User::factory()->create([
            'profile' => 'Sales',
            'name' => 'Promotor Colectivo',
        ]);

        [$groupPlan, $groupPolicy] = $this->createGroupRootPolicy($salesUser, members: 3);

        $this->actingAs($admin);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->set('preregistrationType', PolicyPreregistration::TYPE_GROUP_MEMBER)
            ->set('preregistrationPhone', '3310000050')
            ->set('preregistrationParentPolicy', (string) $groupPolicy->id)
            ->set('preregistrationSalesUser', (string) $salesUser->id)
            ->call('savePreregistration')
            ->assertHasNoErrors()
            ->assertSet('lastPreregistrationPhone', '3310000050')
            ->assertSet('lastPreregistrationPlanName', $groupPlan->name);

        $this->assertDatabaseHas('policy_preregistrations', [
            'sales_user_id' => $salesUser->id,
            'plan_id' => $groupPlan->id,
            'parent_policy_id' => $groupPolicy->id,
            'preregistration_type' => PolicyPreregistration::TYPE_GROUP_MEMBER,
            'phone' => '3310000050',
        ]);
    }

    public function test_cannot_create_group_member_preregistration_when_collective_capacity_is_full(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        [$groupPlan, $groupPolicy] = $this->createGroupRootPolicy($salesUser, members: 2);

        $this->createGroupMemberPolicy($groupPolicy, $groupPlan, '3310000051');

        PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $groupPlan->id,
            'parent_policy_id' => $groupPolicy->id,
            'preregistration_type' => PolicyPreregistration::TYPE_GROUP_MEMBER,
            'phone' => '3310000052',
            'token_hash' => hash('sha256', 'full-group-prereg'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($salesUser);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->set('preregistrationType', PolicyPreregistration::TYPE_GROUP_MEMBER)
            ->set('preregistrationPhone', '3310000053')
            ->set('preregistrationParentPolicy', (string) $groupPolicy->id)
            ->call('savePreregistration')
            ->assertHasErrors(['preregistrationParentPolicy']);
    }

    public function test_cancelling_group_member_preregistration_frees_collective_capacity(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        [$groupPlan, $groupPolicy] = $this->createGroupRootPolicy($salesUser, members: 1);

        $preregistration = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $groupPlan->id,
            'parent_policy_id' => $groupPolicy->id,
            'preregistration_type' => PolicyPreregistration::TYPE_GROUP_MEMBER,
            'phone' => '3310000054',
            'token_hash' => hash('sha256', 'cancel-group-prereg'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($salesUser);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->call('promptPreregistrationCancellation', $preregistration->id)
            ->call('cancelPreregistration')
            ->assertHasNoErrors();

        Livewire::test(PolicyPreregistrationsPage::class)
            ->set('preregistrationType', PolicyPreregistration::TYPE_GROUP_MEMBER)
            ->set('preregistrationPhone', '3310000055')
            ->set('preregistrationParentPolicy', (string) $groupPolicy->id)
            ->call('savePreregistration')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('policy_preregistrations', [
            'parent_policy_id' => $groupPolicy->id,
            'phone' => '3310000055',
            'preregistration_type' => PolicyPreregistration::TYPE_GROUP_MEMBER,
        ]);
    }

    public function test_inactive_group_members_do_not_consume_collective_capacity(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        [$groupPlan, $groupPolicy] = $this->createGroupRootPolicy($salesUser, members: 1);

        $inactiveMember = $this->createGroupMemberPolicy($groupPolicy, $groupPlan, '3310000058');
        $inactiveMember->update([
            'status' => 'Inactive',
        ]);

        $this->actingAs($salesUser);

        Livewire::test(PolicyPreregistrationsPage::class)
            ->set('preregistrationType', PolicyPreregistration::TYPE_GROUP_MEMBER)
            ->set('preregistrationPhone', '3310000059')
            ->set('preregistrationParentPolicy', (string) $groupPolicy->id)
            ->call('savePreregistration')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('policy_preregistrations', [
            'parent_policy_id' => $groupPolicy->id,
            'phone' => '3310000059',
            'preregistration_type' => PolicyPreregistration::TYPE_GROUP_MEMBER,
        ]);
    }

    public function test_group_member_preregistration_page_creates_member_policy_under_collective_parent(): void
    {
        Storage::fake('public');

        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        [$groupPlan, $groupPolicy] = $this->createGroupRootPolicy($salesUser, members: 4);

        $token = 'group-member-token';

        $preregistration = PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $groupPlan->id,
            'parent_policy_id' => $groupPolicy->id,
            'preregistration_type' => PolicyPreregistration::TYPE_GROUP_MEMBER,
            'phone' => '3310000056',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        Livewire::test(PolicyPreregistrationPage::class, ['token' => $token])
            ->set('form.attachment', UploadedFile::fake()->image('member.jpg'))
            ->set('form.name', 'Miembro Colectivo')
            ->set('form.email', 'miembro.colectivo@example.com')
            ->set('form.birth', '1992-04-12')
            ->set('form.curp', 'MICO920412HDFAAA01')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('registrationCompleted', true)
            ->assertSet('registeredMemberName', 'Miembro Colectivo');

        $user = User::query()->where('phone', '3310000056')->firstOrFail();
        $policy = Policy::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Member', $policy->type);
        $this->assertSame($groupPolicy->id, $policy->parent_policy_id);
        $this->assertSame($groupPlan->id, $policy->plan_id);
        $this->assertSame($salesUser->id, $policy->sales_user_id);
        $this->assertSame($preregistration->id, $policy->policy_preregistration_id);

        $preregistration->refresh();

        $this->assertNotNull($preregistration->used_at);
    }

    public function test_manual_group_member_creation_is_blocked_when_pending_preregistrations_fill_capacity(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
        ]);

        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        [$groupPlan, $groupPolicy] = $this->createGroupRootPolicy($salesUser, members: 1);

        PolicyPreregistration::query()->create([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $groupPlan->id,
            'parent_policy_id' => $groupPolicy->id,
            'preregistration_type' => PolicyPreregistration::TYPE_GROUP_MEMBER,
            'phone' => '3310000057',
            'token_hash' => hash('sha256', 'reserved-slot'),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin);

        Livewire::test(IndividualPolicyPage::class, ['policyId' => $groupPolicy->id, 'newMember' => true])
            ->call('save')
            ->assertHasErrors(['form.parent_policy']);
    }

    /**
     * @return array{0: Plan, 1: Policy}
     */
    private function createGroupRootPolicy(User $salesUser, int $members = 3): array
    {
        $groupPlan = Plan::query()->create([
            'name' => 'Plan Colectivo '.$members,
            'price' => 2200.00,
            'type' => 'Group',
            'status' => 'Active',
        ]);

        $owner = User::factory()->create([
            'profile' => 'User',
            'phone' => '3320000'.str_pad((string) $members, 3, '0', STR_PAD_LEFT),
        ]);

        $groupPolicy = Policy::query()->create([
            'user_id' => $owner->id,
            'sales_user_id' => $salesUser->id,
            'plan_id' => $groupPlan->id,
            'number' => 'GRP-'.$members.'-'.rand(100, 999),
            'type' => 'Group',
            'members' => $members,
            'status' => 'Inactive',
        ]);

        return [$groupPlan, $groupPolicy];
    }

    private function createGroupMemberPolicy(Policy $groupPolicy, Plan $groupPlan, string $phone): Policy
    {
        $member = User::factory()->create([
            'profile' => 'User',
            'phone' => $phone,
        ]);

        return Policy::query()->create([
            'user_id' => $member->id,
            'sales_user_id' => $groupPolicy->sales_user_id,
            'plan_id' => $groupPlan->id,
            'parent_policy_id' => $groupPolicy->id,
            'number' => $groupPolicy->number.'-'.substr($phone, -2),
            'type' => 'Member',
            'status' => 'Active',
        ]);
    }
}
