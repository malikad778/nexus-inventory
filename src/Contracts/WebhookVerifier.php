<?php

namespace Adnan\LaravelNexus\Contracts;

use Illuminate\Http\Request;

interface WebhookVerifier
{
    public function verify(Request $request): bool;
}
