<div wire:poll.3s>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($stats as $channel => $data)
            <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm font-medium text-gray-500 truncate uppercase">
                            {{ $channel }}
                        </dt>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $data['health'] === 'Healthy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $stats[$channel]['health'] }}
                        </span>
                    </div>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        {{ $data['pending'] }} <span class="text-sm text-gray-400 font-normal">Pending</span>
                    </dd>
                    <div class="mt-4">
                        <p class="text-xs text-gray-500">
                            Last Sync: {{ $data['last_sync'] ? \Illuminate\Support\Carbon::parse($data['last_sync'])->diffForHumans() : 'Never' }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
