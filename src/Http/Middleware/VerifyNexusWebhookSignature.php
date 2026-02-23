<?php

namespace Malikad778\LaravelNexus\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Malikad778\LaravelNexus\Contracts\WebhookVerifier;
use Malikad778\LaravelNexus\Facades\Nexus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VerifyNexusWebhookSignature
{
    public function handle(Request $request, Closure $next, ?string $channel = null): Response
    {
        if (! $channel) {
            $channel = $request->route('channel');
        }

        $verifier = $this->resolveVerifier($channel);

        if ($verifier && ! $verifier->verify($request)) {
            $topic = 'unknown';
            try {
                $topic = Nexus::driver($channel)->extractWebhookTopic($request);
            } catch (\Exception $e) {
                
            }

            DB::table('nexus_webhook_logs')->insert([
                'channel' => $channel,
                'topic' => $topic,
                'payload' => $request->getContent(),
                'headers' => json_encode($request->headers->all()),
                'status' => 'failed',
                'exception' => 'Invalid webhook signature.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        return $next($request);
    }

    protected function resolveVerifier(?string $channel): ?WebhookVerifier
    {
        if (! $channel) {
            return null;
        }

        try {
            return Nexus::driver($channel)->getWebhookVerifier();
        } catch (\Exception $e) {
            return null;
        }
    }
}
