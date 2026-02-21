<?php

namespace Tests\Feature;

use App\Livewire\Settings\LegalSettingsPage;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LegalSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_legal_settings_page(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('settings.legal'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_legal_settings_page(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('settings.legal'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_terms_version_as_active(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(LegalSettingsPage::class)
            ->set('termsVersion', 'v1.0')
            ->set('termsTitle', 'Terminos y condiciones')
            ->set('termsContent', 'Texto legal suficientemente largo para validar la version de terminos.')
            ->set('termsActivate', true)
            ->call('saveTerms')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('legal_documents', [
            'type' => LegalDocument::TYPE_TERMS,
            'version' => 'v1.0',
            'is_active' => true,
        ]);
    }

    public function test_activate_existing_terms_version_deactivates_previous_active(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $first = LegalDocument::query()->create([
            'type' => LegalDocument::TYPE_TERMS,
            'version' => 'v1.0',
            'title' => 'Terminos',
            'content' => 'Primera version de terminos con contenido legal suficiente.',
            'is_active' => true,
            'effective_at' => now()->subDay(),
            'activated_at' => now()->subDay(),
            'created_by' => $admin->id,
            'activated_by' => $admin->id,
        ]);

        $second = LegalDocument::query()->create([
            'type' => LegalDocument::TYPE_TERMS,
            'version' => 'v1.1',
            'title' => 'Terminos',
            'content' => 'Segunda version de terminos con contenido legal suficiente.',
            'is_active' => false,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(LegalSettingsPage::class)
            ->call('activateDocument', $second->id)
            ->assertHasNoErrors();

        $first->refresh();
        $second->refresh();

        $this->assertFalse($first->is_active);
        $this->assertTrue($second->is_active);
    }
}
