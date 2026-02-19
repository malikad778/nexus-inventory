<?php

use Adnan\LaravelNexus\Webhooks\Verifiers\AmazonWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

it('verifies valid amazon sns signature', function () {
        // Generate a valid 2048-bit RSA key for testing
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        
        // On some Windows setups, openssl.cnf location might be missing, 
        // leading to "error:0E06D06C:configuration file routines:NCONF_get_string:no value"
        // attempting to find it or suppress errors if it fails strictly.
        $res = openssl_pkey_new($config);

        if (!$res) {
             // Fallback or skip if OpenSSL is truly broken in this environment
             $this->markTestSkipped('OpenSSL Key Generation failed: ' . openssl_error_string());
             return;
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
