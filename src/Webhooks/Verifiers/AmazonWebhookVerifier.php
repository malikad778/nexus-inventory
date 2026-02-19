<?php

namespace Adnan\LaravelNexus\Webhooks\Verifiers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AmazonWebhookVerifier implements WebhookVerifier
{
    public function verify(Request $request): bool
    {
        // Amazon SNS sends JSON body
        $payload = $request->json()->all();

        if (empty($payload['Signature']) || empty($payload['SigningCertURL'])) {
            return false;
        }

        $certUrl = $payload['SigningCertURL'];
        
        // Security check: Ensure cert URL is actually from Amazon
        if (! preg_match('/^https:\/\/sns\.[a-zA-Z0-9-]{3,}\.amazonaws\.com(\.cn)?\//', $certUrl)) {
            Log::warning("Invalid SNS Certificate URL: {$certUrl}");
            return false;
        }

        // Get Certificate (Cache it)
        $certificate = Cache::remember('nexus:sns:cert:' . md5($certUrl), 3600, function () use ($certUrl) {
            $response = Http::get($certUrl);
            return $response->successful() ? $response->body() : null;
        });

        if (! $certificate) {
            return false;
        }

        // Reconstruct the string to sign
        $stringToSign = $this->buildStringToSign($payload);
        
        // Verify signature
        $publicKey = openssl_get_publickey($certificate);
        if (! $publicKey) {
             return false;
        }
        
        $signature = base64_decode($payload['Signature']);
        
        $result = openssl_verify($stringToSign, $signature, $publicKey, OPENSSL_ALGO_SHA1);

        return $result === 1;
    }

    protected function buildStringToSign(array $message): string
    {
        $signableKeys = [
            'Message',
            'MessageId',
            'Subject',
            'SubscribeURL',
            'Timestamp',
            'Token',
            'TopicArn',
            'Type',
        ];

        $stringToSign = '';

        foreach ($signableKeys as $key) {
            if (isset($message[$key])) {
                $stringToSign .= "{$key}\n{$message[$key]}\n";
            }
        }

        return $stringToSign;
    }
}
