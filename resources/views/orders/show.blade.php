<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Bestellung {{ $order->order_number }}</h4>
        <div class="btn-group btn-group-sm">
            @can('orders.edit')
                @if($order->canBeModified())
                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-outline-primary">Bearbeiten</a>
                @endif
            @endcan
            @can('orders.view-history')
                <a href="{{ route('orders.history', $order) }}" class="btn btn-outline-secondary">Verlauf</a>
            @endcan
            @if($order->status->value === 'packed' && !$order->delivery_note_path)
                @can('orders.print')
                    <form action="{{ route('delivery-notes.generate', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-outline-success">Lieferschein erstellen</button>
                    </form>
                @endcan
            @endif
            @if($order->delivery_note_path)
                @can('orders.print')
                    <form action="{{ route('delivery-notes.print', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-outline-warning">Lieferschein drucken</button>
                    </form>
                    <a href="{{ route('delivery-notes.download', $order) }}" class="btn btn-outline-secondary">Download</a>
                @endcan
            @endif
        </div>
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title">Bestellinformationen</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5">Bestellnummer:</dt>
                        <dd class="col-sm-7">{{ $order->order_number }}</dd>
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7"><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></dd>
                        <dt class="col-sm-5">Erstellt am:</dt>
                        <dd class="col-sm-7">{{ $order->created_at->format('d.m.Y H:i') }}</dd>
                        <dt class="col-sm-5">Erstellt von:</dt>
                        <dd class="col-sm-7">{{ $order->creator->name }}</dd>
                        @if($order->packed_by)
                            <dt class="col-sm-5">Verpackt von:</dt>
                            <dd class="col-sm-7">{{ $order->packer->name }}</dd>
                        @endif
                        @if($order->delivered_at)
                            <dt class="col-sm-5">Zugestellt am:</dt>
                            <dd class="col-sm-7">{{ $order->delivered_at->format('d.m.Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title">Kundeninformationen</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Name:</dt>
                        <dd class="col-sm-8">{{ $order->customer_name }}</dd>
                        @if($order->customer_email)
                            <dt class="col-sm-4">E-Mail:</dt>
                            <dd class="col-sm-8">{{ $order->customer_email }}</dd>
                        @endif
                        @if($order->customer_phone)
                            <dt class="col-sm-4">Telefon:</dt>
                            <dd class="col-sm-8">{{ $order->customer_phone }}</dd>
                        @endif
                        @if($order->customer_address)
                            <dt class="col-sm-4">Adresse:</dt>
                            <dd class="col-sm-8">{{ $order->customer_address }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title">Status ändern</h6>
                    <form action="{{ route('orders.update-status', $order) }}" method="POST">
                        @csrf @method('PATCH')
                        <select name="status" class="form-select form-select-sm mb-2">
                            <option value="new" {{ $order->status->value === 'new' ? 'selected' : '' }}>Neu</option>
                            <option value="in_progress" {{ $order->status->value === 'in_progress' ? 'selected' : '' }}>In Bearbeitung</option>
                            <option value="packed" {{ $order->status->value === 'packed' ? 'selected' : '' }}>Verpackt</option>
                            <option value="in_delivery" {{ $order->status->value === 'in_delivery' ? 'selected' : '' }}>In Zustellung</option>
                            <option value="delivered" {{ $order->status->value === 'delivered' ? 'selected' : '' }}>Zugestellt</option>
                        </select>
                        <button class="btn btn-primary btn-sm w-100">Status aktualisieren</button>
                    </form>
                    @if($order->notes)
                        <div class="mt-3 small">
                            <strong>Notizen:</strong>
                            <p class="mb-0">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="card-title">Artikel</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Artikel</th>
                            <th>SKU</th>
                            <th>Bestellt</th>
                            <th>Verpackt</th>
                            <th>Status</th>
                            <th>Notizen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->article_name }}</td>
                                <td>{{ $item->article_sku ?? '-' }}</td>
                                <td>{{ $item->quantity_ordered }}</td>
                                <td>{{ $item->quantity_packed }}</td>
                                <td>
                                    @if($item->is_packed)
                                        <span class="badge bg-success">Verpackt</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Offen</span>
                                    @endif
                                </td>
                                <td>{{ $item->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
