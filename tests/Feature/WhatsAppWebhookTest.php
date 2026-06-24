<?php

namespace Tests\Feature;

use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageAttachment;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_can_verify_the_whatsapp_webhook(): void
    {
        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'default_language' => 'es_MX',
            'webhook_verify_token' => 'verify_token_123',
        ]);

        $response = $this->get(route('webhooks.whatsapp.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'verify_token_123',
            'hub.challenge' => 'challenge-abc',
        ]));

        $response
            ->assertOk()
            ->assertContent('challenge-abc');
    }

    public function test_webhook_persists_an_inbound_message_with_a_valid_signature(): void
    {
        $setting = WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'default_language' => 'es_MX',
            'app_secret' => 'meta_app_secret_12345',
        ]);

        $contact = WhatsAppContact::query()->create([
            'name' => 'Prospecto archivado',
            'phone' => '5213318259507',
            'normalized_phone' => '523318259507',
            'wa_id' => '5213318259507',
            'unread_count' => 0,
        ]);

        $conversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $contact->id,
            'status' => 'archived',
            'archived_at' => now()->subHour(),
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '5213310000000',
                                    'phone_number_id' => '113206948334320',
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Juan Perez'],
                                        'wa_id' => '5213318259507',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '5213318259507',
                                        'id' => 'wamid.INBOUND001',
                                        'timestamp' => '1770000000',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hola, quiero informacion.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postWebhookPayload($payload, $setting->app_secret);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertDatabaseHas('whatsapp_webhook_events', [
            'meta_object' => 'whatsapp_business_account',
            'event_type' => 'message',
            'signature_valid' => true,
        ]);

        $this->assertDatabaseHas('whatsapp_contacts', [
            'name' => 'Juan Perez',
            'phone' => '5213318259507',
            'normalized_phone' => '523318259507',
            'wa_id' => '5213318259507',
            'unread_count' => 1,
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'meta_message_id' => 'wamid.INBOUND001',
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'status' => 'received',
            'body_text' => 'Hola, quiero informacion.',
            'from_phone' => '523318259507',
            'to_phone' => '5213310000000',
        ]);

        $this->assertDatabaseHas('whatsapp_message_statuses', [
            'status' => 'received',
        ]);

        $conversation->refresh();
        $this->assertSame('open', $conversation->status);
        $this->assertNull($conversation->archived_at);

        $this->assertSame('ok', $setting->fresh()->webhook_last_status);
        $this->assertNotNull($setting->fresh()->webhook_last_received_at);
    }

    public function test_webhook_updates_delivery_status_for_an_existing_outbound_message(): void
    {
        $setting = WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'default_language' => 'es_MX',
            'app_secret' => 'meta_app_secret_12345',
        ]);

        $contact = WhatsAppContact::query()->create([
            'name' => 'Maria Lopez',
            'phone' => '3311112233',
            'normalized_phone' => '523311112233',
        ]);

        $conversation = WhatsAppConversation::query()->create([
            'whatsapp_contact_id' => $contact->id,
            'status' => 'open',
        ]);

        $message = WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => 'wamid.OUTBOUND001',
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'type' => 'template',
            'status' => 'sent',
            'to_phone' => '523311112233',
            'template_name' => 'activation_pin_template',
            'template_language_code' => 'es_MX',
            'body_text' => '[Template] activation_pin_template',
            'sent_at' => now(),
        ]);

        $message->statuses()->create([
            'status' => 'sent',
            'meta_occurred_at' => now(),
            'payload' => ['seed' => true],
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'wamid.OUTBOUND001',
                                        'status' => 'delivered',
                                        'timestamp' => '1770000500',
                                        'recipient_id' => '5213311112233',
                                        'conversation' => [
                                            'id' => 'conversation-meta-123',
                                        ],
                                        'pricing' => [
                                            'category' => 'utility',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postWebhookPayload($payload, $setting->app_secret);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $message->refresh();

        $this->assertSame('delivered', $message->status);
        $this->assertSame('conversation-meta-123', $message->meta_conversation_id);
        $this->assertSame('utility', $message->meta_pricing_category);
        $this->assertNotNull($message->delivered_at);
        $this->assertDatabaseHas('whatsapp_message_statuses', [
            'whatsapp_message_id' => $message->id,
            'status' => 'delivered',
        ]);
    }

    public function test_webhook_rejects_invalid_signature_without_processing_messages(): void
    {
        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'default_language' => 'es_MX',
            'app_secret' => 'meta_app_secret_12345',
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '5213318259507',
                                        'id' => 'wamid.INVALID001',
                                        'timestamp' => '1770000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'No deberia persistirse'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = $this->call(
            'POST',
            route('webhooks.whatsapp.receive'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
            ],
            $json
        );

        $response->assertStatus(401);

        $this->assertDatabaseHas('whatsapp_webhook_events', [
            'event_type' => 'message',
            'signature_valid' => false,
        ]);

        $this->assertDatabaseMissing('whatsapp_messages', [
            'meta_message_id' => 'wamid.INVALID001',
        ]);

        $setting = WhatsAppSetting::query()->firstOrFail();
        $this->assertSame('invalid_signature', $setting->webhook_last_status);
    }

    public function test_webhook_persists_and_downloads_an_inbound_image_attachment(): void
    {
        Storage::fake('local');

        $setting = WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'default_language' => 'es_MX',
            'app_secret' => 'meta_app_secret_12345',
        ]);

        Http::fake([
            'https://graph.facebook.com/v22.0/media-image-001*' => Http::response([
                'messaging_product' => 'whatsapp',
                'url' => 'https://lookaside.fbsbx.com/whatsapp/media-image-001',
                'mime_type' => 'image/jpeg',
                'sha256' => 'abc123sha',
                'file_size' => 12345,
                'id' => 'media-image-001',
            ], 200),
            'https://lookaside.fbsbx.com/whatsapp/media-image-001' => Http::response('fake-image-binary', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '5213310000000',
                            'phone_number_id' => '113206948334320',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Ana Imagen'],
                            'wa_id' => '5213319990001',
                        ]],
                        'messages' => [[
                            'from' => '5213319990001',
                            'id' => 'wamid.INBOUND.IMAGE.001',
                            'timestamp' => '1770000001',
                            'type' => 'image',
                            'image' => [
                                'id' => 'media-image-001',
                                'mime_type' => 'image/jpeg',
                                'sha256' => 'abc123sha',
                                'caption' => 'Comprobante',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postWebhookPayload($payload, $setting->app_secret);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $attachment = WhatsAppMessageAttachment::query()->firstOrFail();

        $this->assertSame('image', $attachment->type);
        $this->assertSame('Comprobante', $attachment->caption);
        $this->assertSame(WhatsAppMessageAttachment::STATUS_DOWNLOADED, $attachment->download_status);
        $this->assertSame('local', $attachment->storage_disk);
        $this->assertNotNull($attachment->storage_path);
        Storage::disk('local')->assertExists($attachment->storage_path);
    }

    /**
     * Post a raw webhook payload using the same signature Meta sends.
     *
     * @param  array<string, mixed>  $payload
     */
    private function postWebhookPayload(array $payload, string $appSecret)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $json, $appSecret);

        return $this->call(
            'POST',
            route('webhooks.whatsapp.receive'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $json
        );
    }
}
