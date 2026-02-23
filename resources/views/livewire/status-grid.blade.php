<div wire:poll.3s>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($stats as $channel => $data)
            <div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm font-medium text-gray-500 truncate uppercase">
                            {{ $channel }}
                        </dt>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $data['health'] === 'Connected' ? 'bg-green-100 text-green-800' : 
                               ($data['health'] === 'Throttled' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ $data['health'] }}
                        </span>
                    </div>
                    <dd class="mt-1 flex items-baseline justify-between">
                        <div class="text-3xl font-semibold text-gray-900">
                            {{ $data['pending'] }} <span class="text-sm text-gray-400 font-normal">Pending</span>
                        </div>
                        @if($data['tokens'] !== null)
                            <div class="ml-2 flex flex-col items-end">
                                <span class="text-xs text-gray-400">Tokens</span>
                                <div class="w-16 bg-gray-200 rounded-full h-1.5 mt-0.5">
                                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ ($data['tokens'] / $data['capacity']) * 100 }}%"></div>
                                </div>
                                <span class="text-[10px] text-gray-500">{{ $data['tokens'] }} / {{ $data['capacity'] }}</span>
                            </div>
                        @endif
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
