<?php

namespace Tests\Feature;

use App\Livewire\WhatsApp\WhatsAppConsolePage;
use App\Models\User;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_admin_can_send_text_reply_from_console(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'default_language' => 'es_MX',
        ]);

        $contact = WhatsAppContact::query()->create([
            'name' => 'Cliente Reply',
            'phone' => '3315556677',
            'normalized_phone' => '523315556677',
            'wa_id' => '5213315556677',
            'unread_count' => 1,
            'last_message_at' => now(),
        ]);

        $conversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $contact->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => 'wamid.INBOUND-REPLY-1',
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'type' => 'text',
            'status' => 'received',
            'from_phone' => '523315556677',
            'body_text' => 'Hola, necesito ayuda',
            'received_at' => now(),
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '5213315556677', 'wa_id' => '5213315556677']],
                'messages' => [['id' => 'wamid.OUTBOUND-REPLY-1']],
            ], 200),
        ]);

        $this->actingAs($admin);

        Livewire::test(WhatsAppConsolePage::class)
            ->call('selectConversation', $conversation->id)
            ->set('replyMessage', 'Claro, con gusto te apoyamos.')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSet('replyMessage', '');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '5213315556677'
                && $request['type'] === 'text'
                && ($request['text']['body'] ?? null) === 'Claro, con gusto te apoyamos.'
                && ($request['text']['preview_url'] ?? null) === false;
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => 'wamid.OUTBOUND-REPLY-1',
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'type' => 'text',
            'status' => 'sent',
            'to_phone' => '523315556677',
            'body_text' => 'Claro, con gusto te apoyamos.',
        ]);
    }
}
