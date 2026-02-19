<?php

namespace Adnan\LaravelNexus\Http\Middleware;

use Adnan\LaravelNexus\Contracts\WebhookVerifier;
use Adnan\LaravelNexus\Facades\Nexus;
use Closure;
use Illuminate\Http\Request;
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
            \Illuminate\Support\Facades\DB::table('nexus_webhook_logs')->insert([
                'channel' => $channel,
                'topic' => $request->header('X-Shopify-Topic')
                    ?? $request->header('X-GitHub-Event')
                    ?? $request->json('Type')
                    ?? 'unknown',
                'payload' => json_encode($request->all()),
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
