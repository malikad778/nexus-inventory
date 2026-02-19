<?php

namespace Adnan\LaravelNexus\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Adnan\LaravelNexus\Events\WebhookReceived;
use Adnan\LaravelNexus\Http\Middleware\VerifyNexusWebhookSignature;

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
        // Log the webhook
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

        // Dispatch Event
        WebhookReceived::dispatch(
            $channel,
            $request->all(),
            $request->headers->all(),
            $logId
        );

        return response()->json(['status' => 'received']);
    }
}
