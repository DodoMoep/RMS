<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Bestellung verpacken - {{ $order->order_number }}
            </h2>
            <a href="{{ route('packing.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                Zurück zur Übersicht
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('ok'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('ok') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $totalItems = $order->items->count();
                $packedItems = $order->items->where('is_packed', true)->count();
                $percentage = $totalItems > 0 ? ($packedItems / $totalItems) * 100 : 0;
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">Kunde: {{ $order->customer_name }}</h3>
                            <p class="text-sm text-gray-500">Erstellt von: {{ $order->creator->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Fortschritt</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $packedItems }} / {{ $totalItems }}</p>
                        </div>
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Artikel-Checkliste</h3>
                    <div class="space-y-4">
                        @foreach($order->items as $item)
                            <div class="border rounded-lg p-4 {{ $item->is_packed ? 'bg-green-50 border-green-300' : 'bg-white border-gray-300' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            @if($item->is_packed)
                                                <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                            <div>
                                                <h4 class="font-semibold {{ $item->is_packed ? 'text-green-800' : 'text-gray-900' }}">
                                                    {{ $item->item_name }}
                                                </h4>
                                                <p class="text-sm {{ $item->is_packed ? 'text-green-600' : 'text-gray-500' }}">
                                                    SKU: {{ $item->item_sku ?? 'N/A' }} | 
                                                    Menge: {{ $item->quantity_packed }}/{{ $item->quantity_ordered }}
                                                </p>
                                                @if($item->notes)
                                                    <p class="text-sm text-gray-600 mt-1">
                                                        <strong>Hinweis:</strong> {{ $item->notes }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        @if(!$item->is_packed)
                                            <form action="{{ route('packing.pack-item', $item) }}" method="POST" class="inline">
                                                @csrf
                                                @if($item->quantity_ordered > 1)
                                                    <input type="number" name="quantity" min="1" max="{{ $item->remainingQuantity() }}" value="{{ $item->remainingQuantity() }}" class="w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2">
                                                @endif
                                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                                    {{ $item->quantity_ordered > 1 ? 'Teilweise verpacken' : 'Verpackt' }}
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('packing.unpack-item', $item) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                                                    Rückgängig
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($order->isFullyPacked())
                <div class="bg-green-50 border-2 border-green-500 rounded-lg p-6 text-center">
                    <h3 class="text-xl font-bold text-green-800 mb-2">Alle Artikel verpackt!</h3>
                    <p class="text-green-700 mb-4">Die Bestellung ist bereit zum Abschluss.</p>
                    <form action="{{ route('packing.complete', $order) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 text-lg font-semibold">
                            Bestellung abschließen
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
