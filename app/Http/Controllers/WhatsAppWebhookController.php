<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify webhook endpoint with Meta.
     */
    public function verify(Request $request): Response
    {
        $setting = WhatsAppSetting::query()->first();

        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $verifyToken = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if (
            $mode === 'subscribe'
            && filled($challenge)
            && filled($verifyToken)
            && hash_equals((string) ($setting?->webhook_verify_token ?? ''), (string) $verifyToken)
        ) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Invalid verification token.', 403);
    }

    /**
     * Receive and process webhook payloads from Meta.
     */
    public function receive(Request $request, WhatsAppWebhookService $service): JsonResponse
    {
        try {
            $setting = WhatsAppSetting::query()->first();
            $rawPayload = $request->getContent();
            $signatureHeader = $request->header('X-Hub-Signature-256');
            $signatureValidationEnabled = filled($setting?->app_secret);
            $signatureValid = $signatureValidationEnabled
                ? $service->hasValidSignature($rawPayload, $signatureHeader, $setting?->app_secret)
                : false;
            $payload = $request->json()->all();

            Log::info('WHATSAPP_WEBHOOK_RECEIVED', [
                'setting_id' => $setting?->id,
                'webhook_enabled' => (bool) ($setting?->webhook_enabled ?? false),
                'signature_validation_enabled' => $signatureValidationEnabled,
                'has_app_secret' => filled($setting?->app_secret),
                'signature_header_present' => filled($signatureHeader),
                'signature_header_prefix' => filled($signatureHeader) ? substr((string) $signatureHeader, 0, 20) : null,
                'signature_valid' => $signatureValid,
                'payload_size' => strlen($rawPayload),
                'meta_object' => data_get($payload, 'object'),
                'entry_count' => count((array) data_get($payload, 'entry', [])),
                'message_count' => $service->countMessages($payload),
                'status_count' => $service->countStatuses($payload),
            ]);

            if (! is_array($payload) || $payload === []) {
                $service->updateWebhookStatus('invalid_payload');

                Log::warning('WHATSAPP_WEBHOOK_INVALID_PAYLOAD', [
                    'setting_id' => $setting?->id,
                    'content_type' => $request->header('Content-Type'),
                    'payload_size' => strlen($rawPayload),
                ]);

                return response()->json(['ok' => false, 'message' => 'Invalid payload.'], 422);
            }

            $event = $service->ingest($payload, $signatureValid);

            if ($signatureValidationEnabled && ! $signatureValid) {
                Log::warning('WHATSAPP_WEBHOOK_SIGNATURE_IGNORED', [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'signature_header_present' => filled($signatureHeader),
                    'message_count' => $service->countMessages($payload),
                    'status_count' => $service->countStatuses($payload),
                ]);
            }

            return response()->json([
                'ok' => true,
                'event_id' => $event->id,
            ]);
        } catch (Throwable $exception) {
            $service->updateWebhookStatus('processing_error');

            Log::error('WHATSAPP_WEBHOOK_FAILED', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Webhook processing failed.',
            ], 500);
        }
    }
}
