<?php

namespace Tests\Feature;

use App\Livewire\Settings\ParametersPage;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ParametersSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_parameters_settings_page(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('settings.parameters'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_parameters_settings_page(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('settings.parameters'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_parameter(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(ParametersPage::class)
            ->set('type', 'GENERAL')
            ->set('parameterKey', 'MAX_INTENTOS')
            ->set('description', 'Numero maximo de intentos de login')
            ->set('value', '5')
            ->call('saveParameter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('parameters', [
            'type' => 'GENERAL',
            'key' => 'MAX_INTENTOS',
            'description' => 'Numero maximo de intentos de login',
            'value' => '5',
        ]);
    }

    public function test_parameter_type_key_combination_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        Parameter::query()->create([
            'type' => 'GENERAL',
            'key' => 'MAX_INTENTOS',
            'description' => 'Descripcion base',
            'value' => '5',
        ]);

        $this->actingAs($admin);

        Livewire::test(ParametersPage::class)
            ->set('type', 'GENERAL')
            ->set('parameterKey', 'MAX_INTENTOS')
            ->set('description', 'Descripcion duplicada')
            ->set('value', '8')
            ->call('saveParameter')
            ->assertHasErrors(['key' => ['unique']]);
    }

    public function test_same_key_can_be_reused_with_different_type(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        Parameter::query()->create([
            'type' => 'GENERAL',
            'key' => 'MAX_INTENTOS',
            'description' => 'Descripcion base',
            'value' => '5',
        ]);

        $this->actingAs($admin);

        Livewire::test(ParametersPage::class)
            ->set('type', 'AUTH')
            ->set('parameterKey', 'MAX_INTENTOS')
            ->set('description', 'Intentos para modulo auth')
            ->set('value', '3')
            ->call('saveParameter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('parameters', [
            'type' => 'AUTH',
            'key' => 'MAX_INTENTOS',
            'value' => '3',
        ]);
    }

    public function test_admin_can_filter_parameters_by_type_and_key(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        Parameter::query()->create([
            'type' => 'GENERAL',
            'key' => 'MAX_INTENTOS',
            'description' => 'Intentos',
            'value' => '5',
        ]);

        Parameter::query()->create([
            'type' => 'API',
            'key' => 'TOKEN_TIMEOUT',
            'description' => 'Tiempo de token',
            'value' => '30',
        ]);

        $this->actingAs($admin);

        Livewire::test(ParametersPage::class)
            ->set('filterType', 'GENERAL')
            ->assertSee('MAX_INTENTOS')
            ->assertDontSee('TOKEN_TIMEOUT')
            ->set('filterType', '')
            ->set('filterKey', 'TOKEN_TIMEOUT')
            ->assertSee('TOKEN_TIMEOUT');
    }
}
