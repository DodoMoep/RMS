<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bestellung bearbeiten - {{ $order->order_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('orders.update', $order) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Kundeninformationen</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kundenname *</label>
                                <input type="text" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">E-Mail</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email', $order->customer_email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Telefon</label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Adresse</label>
                                <textarea name="customer_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('customer_address', $order->customer_address) }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Notizen</label>
                                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $order->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Artikel</h3>
                            <button type="button" onclick="addItem()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                + Artikel hinzufügen
                            </button>
                        </div>

                        <div id="items-container">
                            @foreach($order->items as $index => $item)
                                <div class="item-row grid grid-cols-12 gap-4 mb-4 {{ $item->is_packed ? 'opacity-50' : '' }}">
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                    <div class="col-span-6">
                                        <label class="block text-sm font-medium text-gray-700">Artikel *</label>
                                        <select name="items[{{ $index }}][article_id]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $item->is_packed ? 'disabled' : '' }}>
                                            <option value="">-- Artikel wählen --</option>
                                            @foreach($articles as $invItem)
                                                <option value="{{ $invItem->id }}" {{ $item->article_id == $invItem->id ? 'selected' : '' }}>
                                                    {{ $invItem->name }} @if($invItem->sku)({{ $invItem->sku }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($item->is_packed)
                                            <input type="hidden" name="items[{{ $index }}][article_id]" value="{{ $item->article_id }}">
                                            <p class="text-xs text-green-600 mt-1">✓ Verpackt - kann nicht geändert werden</p>
                                        @endif
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Menge *</label>
                                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity_ordered }}" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $item->is_packed ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-span-3">
                                        <label class="block text-sm font-medium text-gray-700">Notizen</label>
                                        <input type="text" name="items[{{ $index }}][notes]" value="{{ $item->notes }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $item->is_packed ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-span-1 flex items-end">
                                        @if(!$item->is_packed)
                                            <button type="button" onclick="removeItem(this)" class="w-full px-2 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                                ×
                                            </button>
                                        @else
                                            <div class="w-full px-2 py-2 bg-gray-300 text-gray-600 rounded-md text-center">
                                                🔒
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <a href="{{ route('orders.show', $order) }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Abbrechen
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Bestellung aktualisieren
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itemIndex = {{ $order->items->count() }};
        const articles = @json($articles);

        function addItem() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row grid grid-cols-12 gap-4 mb-4';
            
            let optionsHtml = '<option value="">-- Artikel wählen --</option>';
            articles.forEach(item => {
                optionsHtml += `<option value="${item.id}">${item.name} ${item.sku ? '(' + item.sku + ')' : ''}</option>`;
            });
            
            newRow.innerHTML = `
                <div class="col-span-6">
                    <label class="block text-sm font-medium text-gray-700">Artikel *</label>
                    <select name="items[${itemIndex}][article_id]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Menge *</label>
                    <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Notizen</label>
                    <input type="text" name="items[${itemIndex}][notes]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="col-span-1 flex items-end">
                    <button type="button" onclick="removeItem(this)" class="w-full px-2 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        ×
                    </button>
                </div>
            `;
            
            container.appendChild(newRow);
            itemIndex++;
        }

        function removeItem(button) {
            const container = document.getElementById('items-container');
            const unpackedRows = container.querySelectorAll('.item-row:not(.opacity-50)');
            if (unpackedRows.length > 1) {
                button.closest('.item-row').remove();
            } else {
                alert('Mindestens ein Artikel muss vorhanden sein.');
            }
        }
    </script>
</x-app-layout>
