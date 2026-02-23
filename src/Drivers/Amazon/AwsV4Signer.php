<?php

namespace Malikad778\LaravelNexus\Drivers\Amazon;

class AwsV4Signer
{
    public function __construct(
        protected string $accessKeyId,
        protected string $secretAccessKey,
        protected string $region,
        protected string $service = 'execute-api'
    ) {}

    public function signRequest(string $method, string $url, array $headers = [], string $payload = ''): array
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $path = $parsedUrl['path'] ?? '/';
        $query = $parsedUrl['query'] ?? '';

        $now = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        $headers['host'] = $host;
        $headers['x-amz-date'] = $now;

        
        $canonicalHeaders = '';
        $signedHeaders = '';
        ksort($headers);
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            $canonicalHeaders .= "{$lowerKey}:{$value}\n";
            $signedHeaders .= "{$lowerKey};";
        }
        $signedHeaders = rtrim($signedHeaders, ';');

        $payloadHash = hash('sha256', $payload);
        $canonicalRequest = "{$method}\n{$path}\n{$query}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

        
        $credentialScope = "{$date}/{$this->region}/{$this->service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$now}\n{$credentialScope}\n".hash('sha256', $canonicalRequest);

        
        $kSecret = 'AWS4'.$this->secretAccessKey;
        $kDate = hash_hmac('sha256', $date, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        
        $authorization = "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        return array_merge($headers, ['Authorization' => $authorization]);
    }
}
