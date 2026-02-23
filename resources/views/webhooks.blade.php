@extends('nexus::layout')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Webhook Logs</h1>
        <p class="mt-1 text-sm text-gray-500">Recent incoming webhooks from all channels.</p>
    </div>

    @livewire('nexus-webhook-log')
@endsection
