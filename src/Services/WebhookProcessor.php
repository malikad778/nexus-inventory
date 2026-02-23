<?php

namespace Malikad778\LaravelNexus\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Malikad778\LaravelNexus\Events\InventoryUpdated;
use Malikad778\LaravelNexus\Events\WebhookReceived;
use Malikad778\LaravelNexus\Facades\Nexus;

class WebhookProcessor
{
    public function process(string $channel, Request $request): void
    {
        try {
            $driver = Nexus::driver($channel);
        } catch (Exception $e) {
            Log::error("WebhookProcessing failed. Channel [{$channel}] driver not found.", ['exception' => $e->getMessage()]);

            return; 
        }

        $topic = $driver->extractWebhookTopic($request);
        $rawPayload = $request->getContent();

        
        $logId = DB::table('nexus_webhook_logs')->insertGetId([
            'channel' => $channel,
            'topic' => $topic,
            'payload' => $rawPayload,
            'headers' => json_encode($request->headers->all()),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        
        WebhookReceived::dispatch(
            $channel,
            json_decode($rawPayload, true) ?? [],
            $request->headers->all(),
            $logId
        );

        
        try {
            $updateDto = $driver->parseWebhookPayload($request);

            try {
                $product = $driver->fetchProduct($updateDto->remoteId);
                $previousQuantity = $product->quantity ?? 0;
            } catch (Exception $e) {
                
                
                
                throw new Exception("Unable to fetch product details for remote ID: {$updateDto->remoteId}. Original Error: {$e->getMessage()}", 0, $e);
            }

            InventoryUpdated::dispatch(
                $channel,
                $product,
                $previousQuantity,
                $updateDto->quantity
            );

            DB::table('nexus_webhook_logs')->where('id', $logId)->update(['status' => 'processed']);

        } catch (Exception $e) {
            DB::table('nexus_webhook_logs')->where('id', $logId)->update([
                'status' => 'failed',
                'exception' => $e->getMessage(),
            ]);

            
            
            Log::error("WebhookProcessing failed for log ID [{$logId}].", ['exception' => $e->getMessage()]);
        }
    }
}
