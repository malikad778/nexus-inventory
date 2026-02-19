<?php

namespace Adnan\LaravelNexus\Http\Controllers;

use Adnan\LaravelNexus\Events\WebhookReceived;
use Adnan\LaravelNexus\Http\Middleware\VerifyNexusWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function __construct()
    {
        // Apply middleware.
        // Note: In Laravel 11 package, middleware registration might differ,
        // but this standard approach works for most.
        $this->middleware(VerifyNexusWebhookSignature::class);
    }

    public function handle(Request $request, string $channel)
    {
        // 1. Log the webhook
        $logId = DB::table('nexus_webhook_logs')->insertGetId([
            'channel' => $channel,
            'topic' => $request->header('X-Shopify-Topic')
                ?? $request->header('X-GitHub-Event')
                ?? $request->json('Type') // Amazon SNS Type
                ?? 'unknown',
            'payload' => json_encode($request->all()),
            'headers' => json_encode($request->headers->all()),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Dispatch generic WebhookReceived (System Level)
        WebhookReceived::dispatch(
            $channel,
            $request->all(),
            $request->headers->all(),
            $logId
        );

        // 3. Parse and Dispatch InventoryUpdated (Domain Level)
        try {
            $driver = \Adnan\LaravelNexus\Facades\Nexus::driver($channel);
            $updateDto = $driver->parseWebhookPayload($request);
            
            \Adnan\LaravelNexus\Events\InventoryUpdated::dispatch(
                $channel,
                $updateDto,
                true
            );
            
            DB::table('nexus_webhook_logs')->where('id', $logId)->update(['status' => 'processed']);
            
        } catch (\Exception $e) {
            DB::table('nexus_webhook_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'exception' => $e->getMessage()
            ]);
            
            // We don't fail the response, just log internally
            // Or maybe we should return 500? Webhooks usually prefer 200 explicitly if received.
        }

        return response()->json(['status' => 'received']);
    }
}
