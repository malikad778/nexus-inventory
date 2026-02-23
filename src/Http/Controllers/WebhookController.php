<?php

namespace Malikad778\LaravelNexus\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Malikad778\LaravelNexus\Http\Middleware\VerifyNexusWebhookSignature;
use Malikad778\LaravelNexus\Services\WebhookProcessor;

class WebhookController extends Controller
{
    public function __construct(protected WebhookProcessor $processor)
    {
        
        
        
        $this->middleware(VerifyNexusWebhookSignature::class);
    }

    public function handle(Request $request, string $channel)
    {
        $this->processor->process($channel, $request);

        return response()->json(['status' => 'received']);
    }
}
