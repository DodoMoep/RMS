<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Bestellverlauf - {{ $order->order_number }}
            </h2>
            <a href="{{ route('orders.show', $order) }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                Zurück zur Bestellung
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-6">Zeitverlauf</h3>
                    
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                        
                        <div class="space-y-6">
                            @foreach($order->history as $history)
                                <div class="relative pl-10">
                                    <div class="absolute left-0 top-1 w-8 h-8 rounded-full {{ 
                                        str_contains($history->event_type, 'created') ? 'bg-blue-500' :
                                        (str_contains($history->event_type, 'status') ? 'bg-purple-500' :
                                        (str_contains($history->event_type, 'packed') ? 'bg-green-500' :
                                        (str_contains($history->event_type, 'deleted') ? 'bg-red-500' : 'bg-gray-500')))
                                    }} flex items-center justify-center">
                                        @if(str_contains($history->event_type, 'created'))
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                            </svg>
                                        @elseif(str_contains($history->event_type, 'packed'))
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @elseif(str_contains($history->event_type, 'status'))
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-semibold text-gray-900">{{ $history->description }}</h4>
                                            <span class="text-xs text-gray-500">{{ $history->created_at->format('d.m.Y H:i') }}</span>
                                        </div>
                                        
                                        <div class="text-sm text-gray-600 space-y-1">
                                            @if($history->user)
                                                <p><strong>Benutzer:</strong> {{ $history->user->name }}</p>
                                            @endif
                                            
                                            <p><strong>Ereignistyp:</strong> <code class="bg-gray-200 px-2 py-0.5 rounded text-xs">{{ $history->event_type }}</code></p>
                                            
                                            @if($history->metadata)
                                                <div class="mt-2">
                                                    <strong>Details:</strong>
                                                    <div class="bg-white p-2 rounded mt-1 text-xs">
                                                        @foreach($history->metadata as $key => $value)
                                                            <div>{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if($history->ip_address)
                                                <p class="text-xs text-gray-400 mt-1">IP: {{ $history->ip_address }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
