<?php

namespace Tests\Feature;

use App\Livewire\WhatsApp\WhatsAppConsolePage;
use App\Models\User;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageAttachment;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

    public function test_console_does_not_select_a_conversation_by_default(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $contact = WhatsAppContact::query()->create([
            'name' => 'Cliente Sin Seleccion',
            'phone' => '3311112233',
            'normalized_phone' => '523311112233',
            'last_message_at' => now(),
        ]);

        WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $contact->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(WhatsAppConsolePage::class)
            ->assertSet('selectedConversationId', null)
            ->assertSee('Selecciona una conversación para ver el detalle.');
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
            ->assertSet('selectedConversationId', $prospectConversation->id);

        Livewire::test(WhatsAppConsolePage::class)
            ->call('selectConversation', $prospectConversation->id)
            ->set('assignedUserId', (string) $sales->id)
            ->assertHasNoErrors();

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
            ->assertSee('Prospecto Uno');

        Livewire::test(WhatsAppConsolePage::class)
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

    public function test_admin_can_preview_and_download_inbound_media_from_console_routes(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $contact = WhatsAppContact::query()->create([
            'name' => 'Cliente Archivo',
            'phone' => '3310000001',
            'normalized_phone' => '523310000001',
            'wa_id' => '5213310000001',
            'unread_count' => 0,
            'last_message_at' => now(),
        ]);

        $conversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $contact->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $message = WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => 'wamid.DOC.001',
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'type' => 'document',
            'status' => 'received',
            'from_phone' => '523310000001',
            'to_phone' => '5213310000000',
            'body_text' => 'Contrato PDF',
            'received_at' => now(),
        ]);

        Storage::disk('local')->put('whatsapp/inbound/test/contrato.pdf', '%PDF-test');

        $attachment = WhatsAppMessageAttachment::query()->create([
            'whatsapp_message_id' => $message->id,
            'provider_media_id' => 'media-doc-001',
            'type' => 'document',
            'mime_type' => 'application/pdf',
            'file_name' => 'contrato.pdf',
            'download_status' => WhatsAppMessageAttachment::STATUS_DOWNLOADED,
            'storage_disk' => 'local',
            'storage_path' => 'whatsapp/inbound/test/contrato.pdf',
            'downloaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('whatsapp.attachments.preview', $attachment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('whatsapp.attachments.download', $attachment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_create_assign_filter_and_remove_tags_from_conversations(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $firstContact = WhatsAppContact::query()->create([
            'name' => 'Prospecto Etiqueta',
            'phone' => '3312001111',
            'normalized_phone' => '523312001111',
            'last_message_at' => now(),
        ]);

        $firstConversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $firstContact->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $firstConversation->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'type' => 'text',
            'status' => 'received',
            'from_phone' => '523312001111',
            'body_text' => 'Mensaje uno',
            'received_at' => now(),
        ]);

        $secondContact = WhatsAppContact::query()->create([
            'name' => 'Prospecto Sin Etiqueta',
            'phone' => '3312002222',
            'normalized_phone' => '523312002222',
            'last_message_at' => now()->subMinute(),
        ]);

        $secondConversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $secondContact->id,
            'status' => 'open',
            'last_message_at' => now()->subMinute(),
        ]);

        WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $secondConversation->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'type' => 'text',
            'status' => 'received',
            'from_phone' => '523312002222',
            'body_text' => 'Mensaje dos',
            'received_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin);

        Livewire::test(WhatsAppConsolePage::class)
            ->call('selectConversation', $firstConversation->id)
            ->set('newTagName', 'Urgente')
            ->set('newTagColor', 'rose')
            ->call('createTag')
            ->assertHasNoErrors();

        $tag = WhatsAppTag::query()->where('name', 'Urgente')->firstOrFail();

        $this->assertDatabaseHas('whatsapp_conversation_tag', [
            'whatsapp_conversation_id' => $firstConversation->id,
            'whatsapp_tag_id' => $tag->id,
        ]);

        Livewire::test(WhatsAppConsolePage::class)
            ->set('filterTagId', (string) $tag->id)
            ->assertSee('Prospecto Etiqueta')
            ->assertDontSee('Prospecto Sin Etiqueta');

        Livewire::test(WhatsAppConsolePage::class)
            ->call('selectConversation', $firstConversation->id)
            ->call('detachTag', $tag->id);

        $this->assertDatabaseMissing('whatsapp_conversation_tag', [
            'whatsapp_conversation_id' => $firstConversation->id,
            'whatsapp_tag_id' => $tag->id,
        ]);
    }
}
