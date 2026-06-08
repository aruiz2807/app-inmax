<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        $setting = WhatsAppSetting::query()->first();
        $rawPayload = $request->getContent();
        $signatureHeader = $request->header('X-Hub-Signature-256');
        $signatureValid = $service->hasValidSignature($rawPayload, $signatureHeader, $setting?->app_secret);

        $payload = $request->json()->all();

        if (! is_array($payload) || $payload === []) {
            return response()->json(['ok' => false, 'message' => 'Invalid payload.'], 422);
        }

        $event = $service->ingest($payload, $signatureValid);

        if (! $signatureValid) {
            $service->updateWebhookStatus('invalid_signature');

            return response()->json([
                'ok' => false,
                'message' => 'Invalid signature.',
                'event_id' => $event->id,
            ], 401);
        }

        return response()->json([
            'ok' => true,
            'event_id' => $event->id,
        ]);
    }
}
