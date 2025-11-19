<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>{{ __('orders.order') }} {{ $order->order_number }}</h4>
        <div>
            @can('orders.edit')
                @if($order->canBeModified())
                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> {{ __('orders.actions.edit') }}
                    </a>
                @endif
            @endcan
            @can('orders.view-history')
                <a href="{{ route('orders.history', $order) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-history"></i> {{ __('orders.history') }}
                </a>
            @endcan
            @if($order->status->value === 'packed' && !$order->delivery_note_path)
                @can('orders.print')
                    <form action="{{ route('delivery-notes.generate', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-file-invoice"></i> {{ __('delivery_notes.generate') }}
                        </button>
                    </form>
                @endcan
            @endif
            @if($order->delivery_note_path)
                @can('orders.print')
                    <form action="{{ route('delivery-notes.print', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-print"></i> {{ __('delivery_notes.print') }}
                        </button>
                    </form>
                    <a href="{{ route('delivery-notes.download', $order) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-download"></i> {{ __('delivery_notes.download') }}
                    </a>
                @endcan
            @endif
        </div>
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title">{{ __('orders.order_information') }}</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5">{{ __('orders.order_number') }}:</dt>
                        <dd class="col-sm-7">{{ $order->order_number }}</dd>
                        <dt class="col-sm-5">{{ __('orders.status') }}:</dt>
                        <dd class="col-sm-7"><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></dd>
                        <dt class="col-sm-5">{{ __('orders.created_at') }}:</dt>
                        <dd class="col-sm-7">{{ $order->created_at->format('d.m.Y H:i') }}</dd>
                        <dt class="col-sm-5">{{ __('orders.created_by') }}:</dt>
                        <dd class="col-sm-7">{{ $order->creator->name }}</dd>
                        @if($order->packed_by)
                            <dt class="col-sm-5">{{ __('orders.packed_by') }}:</dt>
                            <dd class="col-sm-7">{{ $order->packer->name }}</dd>
                        @endif
                        @if($order->delivered_at)
                            <dt class="col-sm-5">{{ __('orders.delivered_at') }}:</dt>
                            <dd class="col-sm-7">{{ $order->delivered_at->format('d.m.Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title">{{ __('orders.customer_information') }}</h6>
                    <dl class="row mb-0 small">
                        @if($order->customer_id)
                            <dt class="col-sm-4">{{ __('orders.customer_number') }}:</dt>
                            <dd class="col-sm-8">{{ $order->customer->customer_number }}</dd>
                        @endif
                        <dt class="col-sm-4">{{ __('orders.customer_name') }}:</dt>
                        <dd class="col-sm-8">{{ $order->customer_name }}</dd>
                        @if($order->customer_email)
                            <dt class="col-sm-4">{{ __('orders.customer_email') }}:</dt>
                            <dd class="col-sm-8">{{ $order->customer_email }}</dd>
                        @endif
                        @if($order->customer_phone)
                            <dt class="col-sm-4">{{ __('orders.customer_phone') }}:</dt>
                            <dd class="col-sm-8">{{ $order->customer_phone }}</dd>
                        @endif
                        @if($order->customer_address)
                            <dt class="col-sm-4">{{ __('orders.customer_address') }}:</dt>
                            <dd class="col-sm-8">{{ $order->customer_address }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    @can('orders.update-status')
                        @if($order->status->value !== 'delivered')
                            <h6 class="card-title">{{ __('orders.actions.change_status') }}</h6>
                            <form action="{{ route('orders.update-status', $order) }}" method="POST">
                                @csrf @method('PATCH')
                                <select name="status" class="form-select form-select-sm mb-2">
                                    <option value="new" {{ $order->status->value === 'new' ? 'selected' : '' }}>{{ __('orders.statuses.new') }}</option>
                                    <option value="in_progress" {{ $order->status->value === 'in_progress' ? 'selected' : '' }}>{{ __('orders.statuses.in_progress') }}</option>
                                    <option value="packed" {{ $order->status->value === 'packed' ? 'selected' : '' }}>{{ __('orders.statuses.packed') }}</option>
                                    <option value="in_delivery" {{ $order->status->value === 'in_delivery' ? 'selected' : '' }}>{{ __('orders.statuses.in_delivery') }}</option>
                                    <option value="delivered" {{ $order->status->value === 'delivered' ? 'selected' : '' }}>{{ __('orders.statuses.delivered') }}</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('orders.update_status') }}</button>
                            </form>
                        @else
                            <h6 class="card-title">{{ __('orders.status') }}</h6>
                            <div class="text-center py-3">
                                <span class="badge {{ $order->status->color() }} fs-6">{{ $order->status->label() }}</span>
                                <p class="text-muted small mt-2 mb-0">{{ __('orders.order_completed') }}</p>
                            </div>
                        @endif
                    @else
                        <h6 class="card-title">{{ __('orders.status') }}</h6>
                        <div class="text-center py-3">
                            <span class="badge {{ $order->status->color() }} fs-6">{{ $order->status->label() }}</span>
                        </div>
                    @endcan
                    @if($order->notes)
                        <div class="mt-3 small">
                            <strong>{{ __('orders.notes') }}:</strong>
                            <p class="mb-0">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="card-title">{{ __('orders.items') }}</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('orders.article') }}</th>
                            <th>{{ __('orders.sku') }}</th>
                            <th>{{ __('orders.ordered') }}</th>
                            <th>{{ __('orders.packed') }}</th>
                            <th>{{ __('orders.status') }}</th>
                            <th>{{ __('orders.notes') }}</th>
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
                                        <span class="badge bg-success">{{ __('orders.item_packed') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ __('orders.item_open') }}</span>
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
