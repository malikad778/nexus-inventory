<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Adnan\LaravelNexus\Webhooks\Verifiers\AmazonWebhookVerifier;

it('verifies valid amazon sns signature', function () {
    // 1. Generate a real key pair for testing
    $config = [
        "digest_alg" => "sha1",
        "private_key_bits" => 1024,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    $res = openssl_pkey_new($config);
    if ($res === false) {
         $this->markTestSkipped('OpenSSL Key Generation failed: ' . openssl_error_string());
    }
    openssl_pkey_export($res, $privateKey);
    $publicKey = openssl_pkey_get_details($res)['key'];
    
    // 2. Mock the certificate URL to return the public key
    $certUrl = 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-example.pem';
    
    Http::fake([
        $certUrl => Http::response($publicKey, 200),
    ]);
    
    // 3. Construct Payload
    $payload = [
        'Type' => 'Notification',
        'MessageId' => '123',
        'TopicArn' => 'arn:aws:sns:us-east-1:123:MyTopic',
        'Message' => 'Hello World',
        'Timestamp' => '2023-01-01T00:00:00.000Z',
        'SignatureVersion' => '1',
        'SigningCertURL' => $certUrl,
    ];
    
    // 4. Calculate Signature
    // AmazonWebhookVerifier builds string to sign using specific keys
    $stringToSign = "Message\nHello World\nMessageId\n123\nTimestamp\n2023-01-01T00:00:00.000Z\nTopicArn\narn:aws:sns:us-east-1:123:MyTopic\nType\nNotification\n";
    
    openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA1);
    $payload['Signature'] = base64_encode($signature);
    
    $request = Request::create('/', 'POST', [], [], [], [], json_encode($payload));
    
    $verifier = new AmazonWebhookVerifier();
    expect($verifier->verify($request))->toBeTrue();
});

it('rejects invalid amazon sns signature', function () {
    $certUrl = 'https://sns.us-east-1.amazonaws.com/example.pem';
    Http::fake([$certUrl => Http::response('fake_cert', 200)]);
    
    $payload = [
        'SigningCertURL' => $certUrl,
        'Signature' => 'invalid',
        'Type' => 'Notification',
    ];
    
    $request = Request::create('/', 'POST', [], [], [], [], json_encode($payload));
    
    $verifier = new AmazonWebhookVerifier();
    expect($verifier->verify($request))->toBeFalse();
});
