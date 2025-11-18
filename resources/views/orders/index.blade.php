<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="get" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche…" class="form-control form-control-sm" style="width:200px;">
            <select name="status" class="form-select form-select-sm" style="width:150px;">
                <option value="">Alle Status</option>
                <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Neu</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                <option value="packed" {{ request('status') == 'packed' ? 'selected' : '' }}>Verpackt</option>
                <option value="in_delivery" {{ request('status') == 'in_delivery' ? 'selected' : '' }}>In Zustellung</option>
                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Zugestellt</option>
            </select>
            <button class="btn btn-outline-secondary btn-sm">Filtern</button>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">Zurücksetzen</a>
        </form>
        @can('orders.create')
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">Neu</a>
        @endcan
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
                        <th>Status</th>
                        <th>Artikel</th>
                        <th>Erstellt am</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}" class="text-decoration-none">{{ $order->order_number }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                            <td>{{ $order->items->count() }}</td>
                            <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-link btn-sm">Ansehen</a>
                                @can('orders.edit')
                                    @if($order->canBeModified())
                                        <a href="{{ route('orders.edit', $order) }}" class="btn btn-link btn-sm">Bearbeiten</a>
                                    @endif
                                @endcan
                                @can('orders.delete')
                                    @if($order->canBeModified())
                                        <form action="{{ route('orders.destroy', $order) }}" method="post" class="d-inline" onsubmit="return confirm('Wirklich löschen?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-link text-danger btn-sm">Löschen</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Keine Bestellungen gefunden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
