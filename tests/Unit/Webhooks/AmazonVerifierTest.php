<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Malikad778\LaravelNexus\Webhooks\Verifiers\AmazonWebhookVerifier;

it('verifies valid amazon sns signature', function () {
    
    $config = [
        'digest_alg' => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    
    
    
    $res = openssl_pkey_new($config);

    if (! $res) {
        
        $this->markTestSkipped('OpenSSL Key Generation failed: '.openssl_error_string());

        return;
    }
    openssl_pkey_export($res, $privateKey);
    $publicKey = openssl_pkey_get_details($res)['key'];

    
    $certUrl = 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-example.pem';

    Http::fake([
        $certUrl => Http::response($publicKey, 200),
    ]);

    
    $payload = [
        'Type' => 'Notification',
        'MessageId' => '123',
        'TopicArn' => 'arn:aws:sns:us-east-1:123:MyTopic',
        'Message' => 'Hello World',
        'Timestamp' => '2023-01-01T00:00:00.000Z',
        'SignatureVersion' => '1',
        'SigningCertURL' => $certUrl,
    ];

    
    
    $stringToSign = "Message\nHello World\nMessageId\n123\nTimestamp\n2023-01-01T00:00:00.000Z\nTopicArn\narn:aws:sns:us-east-1:123:MyTopic\nType\nNotification\n";

    openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA1);
    $payload['Signature'] = base64_encode($signature);

    $request = Request::create('/', 'POST', [], [], [], [], json_encode($payload));

    $verifier = new AmazonWebhookVerifier;
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

    $verifier = new AmazonWebhookVerifier;
    expect($verifier->verify($request))->toBeFalse();
});
