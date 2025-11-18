<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Bestellungs-Analytik
            </h2>
            @can('analytics.export')
                <a href="{{ route('analytics.export') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Bericht exportieren (PDF)
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Gesamt Bestellungen</h3>
                        <p class="text-3xl font-bold mt-2">{{ $totalOrders }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Aktive Bestellungen</h3>
                        <p class="text-3xl font-bold mt-2 text-yellow-600">{{ $activeOrders }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Artikel verpackt (diesen Monat)</h3>
                        <p class="text-3xl font-bold mt-2 text-green-600">{{ $itemsPacked }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Packer aktiv</h3>
                        <p class="text-3xl font-bold mt-2 text-blue-600">{{ $packerPerformance->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Bestellungen nach Status</h3>
                        <div class="space-y-3">
                            @foreach($statusData as $status)
                                @php
                                    $statusEnum = \App\Enums\OrderStatus::from($status->status);
                                @endphp
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium">{{ $statusEnum->label() }}</span>
                                        <span class="text-sm text-gray-500">{{ $status->count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        @php
                                            $percentage = $totalOrders > 0 ? ($status->count / $totalOrders) * 100 : 0;
                                        @endphp
                                        <div class="h-2 rounded-full {{ str_replace(['text-', '100'], ['bg-', '600'], $statusEnum->color()) }}" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Top 10 Artikel</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">Artikel</th>
                                        <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase">Menge</th>
                                        <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase">Bestellungen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topItemsData as $item)
                                        <tr class="border-b">
                                            <td class="py-2 text-sm">{{ $item->article_name }}</td>
                                            <td class="py-2 text-sm text-right">{{ $item->total_quantity }}</td>
                                            <td class="py-2 text-sm text-right">{{ $item->order_count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Series -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Bestellungen über Zeit</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-2 px-4 text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="text-right py-2 px-4 text-xs font-medium text-gray-500 uppercase">Anzahl</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeSeriesData as $data)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-4 text-sm">{{ $data->period }}</td>
                                        <td class="py-2 px-4 text-sm text-right">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">{{ $data->count }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Packer Performance -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Packer-Leistung</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packer</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Bestellungen verpackt</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($packerPerformance as $packer)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $packer->packer_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full font-medium">
                                                {{ $packer->orders_packed }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-4 text-center text-gray-500">
                                            Keine Daten verfügbar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
