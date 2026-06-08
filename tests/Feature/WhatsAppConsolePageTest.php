<?php

namespace Tests\Feature;

use App\Livewire\WhatsApp\WhatsAppConsolePage;
use App\Models\User;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppConsolePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_whatsapp_console_page(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('whatsapp.console'));

        $response->assertOk();
        $response->assertSee('Conversaciones');
    }

    public function test_non_admin_cannot_access_whatsapp_console_page(): void
    {
        $user = User::factory()->create([
            'profile' => 'Sales',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('whatsapp.console'));

        $response->assertStatus(403);
    }

    public function test_admin_can_filter_assign_archive_and_reopen_conversations(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $sales = User::factory()->create([
            'name' => 'Vendedor Console',
            'profile' => 'Sales',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $linkedUser = User::factory()->create([
            'name' => 'Usuario Vinculado',
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $prospectContact = WhatsAppContact::query()->create([
            'name' => 'Prospecto Uno',
            'phone' => '3310001111',
            'normalized_phone' => '523310001111',
            'unread_count' => 2,
            'last_message_at' => now(),
        ]);

        $prospectConversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $prospectContact->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $prospectConversation->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'type' => 'text',
            'status' => 'received',
            'from_phone' => '523310001111',
            'body_text' => 'Mensaje prospecto',
            'received_at' => now(),
        ]);

        $linkedContact = WhatsAppContact::query()->create([
            'user_id' => $linkedUser->id,
            'name' => 'Usuario Vinculado',
            'phone' => '3310002222',
            'normalized_phone' => '523310002222',
            'unread_count' => 0,
            'last_message_at' => now()->subMinutes(5),
        ]);

        $linkedConversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $linkedContact->id,
            'status' => 'archived',
            'archived_at' => now()->subHour(),
            'last_message_at' => now()->subMinutes(5),
        ]);

        WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $linkedConversation->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'type' => 'template',
            'status' => 'delivered',
            'to_phone' => '523310002222',
            'body_text' => 'Mensaje usuario',
            'sent_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($admin);

        Livewire::test(WhatsAppConsolePage::class)
            ->set('linkedFilter', 'prospects')
            ->assertSee('Prospecto Uno')
            ->assertDontSee('Usuario Vinculado')
            ->call('selectConversation', $prospectConversation->id)
            ->set('assignedUserId', (string) $sales->id);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $prospectConversation->id,
            'assigned_user_id' => $sales->id,
        ]);

        $this->assertDatabaseHas('whatsapp_contacts', [
            'id' => $prospectContact->id,
            'unread_count' => 0,
        ]);

        Livewire::test(WhatsAppConsolePage::class)
            ->call('selectConversation', $prospectConversation->id)
            ->call('archiveSelectedConversation');

        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $prospectConversation->id,
            'status' => 'archived',
        ]);

        Livewire::test(WhatsAppConsolePage::class)
            ->set('statusFilter', 'archived')
            ->assertSee('Prospecto Uno')
            ->call('selectConversation', $prospectConversation->id)
            ->call('reopenSelectedConversation');

        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $prospectConversation->id,
            'status' => 'open',
        ]);
    }
}
