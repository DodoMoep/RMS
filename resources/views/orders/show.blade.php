<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Bestellung {{ $order->order_number }}
            </h2>
            <div class="flex gap-2">
                @can('orders.edit')
                    @if($order->canBeModified())
                        <a href="{{ route('orders.edit', $order) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Bearbeiten
                        </a>
                    @endif
                @endcan
                
                @can('orders.view-history')
                    <a href="{{ route('orders.history', $order) }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        Verlauf
                    </a>
                @endcan

                @if($order->status->value === 'packed' && !$order->delivery_note_path)
                    @can('orders.print')
                        <form action="{{ route('delivery-notes.generate', $order) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Lieferschein erstellen
                            </button>
                        </form>
                    @endcan
                @endif

                @if($order->delivery_note_path)
                    @can('orders.print')
                        <form action="{{ route('delivery-notes.print', $order) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                                Lieferschein drucken
                            </button>
                        </form>
                        <a href="{{ route('delivery-notes.download', $order) }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                            Download
                        </a>
                    @endcan
                @endif
            </div>
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Bestellinformationen</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Bestellnummer</dt>
                                <dd class="text-sm text-gray-900">{{ $order->order_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $order->status->color() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Erstellt am</dt>
                                <dd class="text-sm text-gray-900">{{ $order->created_at->format('d.m.Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Erstellt von</dt>
                                <dd class="text-sm text-gray-900">{{ $order->creator->name }}</dd>
                            </div>
                            @if($order->packed_by)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Verpackt von</dt>
                                    <dd class="text-sm text-gray-900">{{ $order->packer->name }}</dd>
                                </div>
                            @endif
                            @if($order->delivered_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Zugestellt am</dt>
                                    <dd class="text-sm text-gray-900">{{ $order->delivered_at->format('d.m.Y H:i') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Kundeninformationen</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="text-sm text-gray-900">{{ $order->customer_name }}</dd>
                            </div>
                            @if($order->customer_email)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">E-Mail</dt>
                                    <dd class="text-sm text-gray-900">{{ $order->customer_email }}</dd>
                                </div>
                            @endif
                            @if($order->customer_phone)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Telefon</dt>
                                    <dd class="text-sm text-gray-900">{{ $order->customer_phone }}</dd>
                                </div>
                            @endif
                            @if($order->customer_address)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                                    <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $order->customer_address }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Status ändern</h3>
                        <form action="{{ route('orders.update-status', $order) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="mb-4">
                                <select name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="new" {{ $order->status->value === 'new' ? 'selected' : '' }}>Neu</option>
                                    <option value="in_progress" {{ $order->status->value === 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                                    <option value="packed" {{ $order->status->value === 'packed' ? 'selected' : '' }}>Verpackt</option>
                                    <option value="in_delivery" {{ $order->status->value === 'in_delivery' ? 'selected' : '' }}>In Zustellung</option>
                                    <option value="delivered" {{ $order->status->value === 'delivered' ? 'selected' : '' }}>Zugestellt</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Status aktualisieren
                            </button>
                        </form>

                        @if($order->notes)
                            <div class="mt-4">
                                <dt class="text-sm font-medium text-gray-500">Notizen</dt>
                                <dd class="text-sm text-gray-900 whitespace-pre-line">{{ $order->notes }}</dd>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Artikel</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Artikel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bestellt</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verpackt</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notizen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->item_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->item_sku }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->quantity_ordered }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->quantity_packed }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($item->is_packed)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Verpackt
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Offen
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $item->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
