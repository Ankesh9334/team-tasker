<?php

namespace App\Http\Controllers;

use App\Services\InboundEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundEmailWebhookController extends Controller
{
    protected InboundEmailService $inboundService;

    public function __construct(InboundEmailService $inboundService)
    {
        $this->inboundService = $inboundService;
    }

    /**
     * Handle incoming email webhook from mail provider.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Webhook Signature / Secret Verification (if configured)
        $configuredSecret = config('services.inbound_email.secret', env('INBOUND_EMAIL_WEBHOOK_SECRET'));
        if (!empty($configuredSecret)) {
            $providedSecret = $request->header('X-Webhook-Secret') 
                ?? $request->header('X-Mail-Secret') 
                ?? $request->query('token') 
                ?? $request->input('secret');

            if (!hash_equals((string) $configuredSecret, (string) $providedSecret)) {
                Log::warning('Inbound email webhook rejected: invalid secret signature.');
                return response()->json(['success' => false, 'error' => 'Unauthorized signature.'], 401);
            }
        }

        try {
            $result = $this->inboundService->processInbound($request);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'ticket_id' => $result['ticket']?->id ?? null,
                'ticket_number' => $result['ticket']?->ticket_number ?? null,
                'inbound_id' => $result['inbound']?->id ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Inbound email processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error processing inbound email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
