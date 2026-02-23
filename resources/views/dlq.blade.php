@extends('nexus::layout')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dead Letter Queue</h1>
        <p class="mt-1 text-sm text-gray-500">Manage failed jobs that require manual intervention.</p>
    </div>

    @livewire('nexus-dead-letter-queue')
@endsection
