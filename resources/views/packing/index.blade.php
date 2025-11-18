<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Verpackung - Offene Bestellungen</h4>
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bestellnummer</th>
                        <th>Kunde</th>
                        <th>Artikel</th>
                        <th>Fortschritt</th>
                        <th>Erstellt am</th>
                        <th class="text-end">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $totalItems = $order->items->count();
                            $packedItems = $order->items->where('is_packed', true)->count();
                            $percentage = $totalItems > 0 ? ($packedItems / $totalItems) * 100 : 0;
                        @endphp
                        <tr>
                            <td><a href="{{ route('packing.show', $order) }}" class="text-decoration-none fw-semibold">{{ $order->order_number }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $totalItems }} Artikel</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">{{ $packedItems }}/{{ $totalItems }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('packing.show', $order) }}" class="btn btn-primary btn-sm">Verpacken</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Keine Bestellungen zum Verpacken vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
