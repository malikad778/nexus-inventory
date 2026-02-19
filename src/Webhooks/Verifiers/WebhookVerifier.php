<?php

namespace Adnan\LaravelNexus\Webhooks\Verifiers;

use Illuminate\Http\Request;

interface WebhookVerifier
{
    public function verify(Request $request): bool;
}
