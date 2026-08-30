<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="get" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('common.common.search') }}…" class="form-control form-control-sm" style="width:200px;">
            <select name="status" class="form-select form-select-sm" style="width:150px;">
                <option value="">{{ __('orders.statuses.all') }}</option>
                <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>{{ __('orders.statuses.new') }}</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>{{ __('orders.statuses.in_progress') }}</option>
                <option value="packed" {{ request('status') == 'packed' ? 'selected' : '' }}>{{ __('orders.statuses.packed') }}</option>
                <option value="in_delivery" {{ request('status') == 'in_delivery' ? 'selected' : '' }}>{{ __('orders.statuses.in_delivery') }}</option>
                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>{{ __('orders.statuses.delivered') }}</option>
            </select>
            <button class="btn btn-outline-secondary btn-sm">{{ __('common.common.filter') }}</button>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('common.common.reset') }}</a>
        </form>
        @can('orders.create')
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">{{ __('common.common.create') }}</a>
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
                        <th>{{ __('orders.order_number') }}</th>
                        <th>{{ __('orders.customer_name') }}</th>
                        <th>{{ __('orders.delivery_date') }}</th>
                        <th>{{ __('orders.delivery_type') }}</th>
                        <th>{{ __('orders.status') }}</th>
                        <th>{{ __('orders.items') }}</th>
                        <th>{{ __('orders.created_at') }}</th>
                        <th class="text-end">{{ __('common.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}" class="text-decoration-none">{{ $order->order_number }}</a></td>
                            <td>{{ $order->contact->name ?? 'N/A' }}</td>
                            <td>{{ $order->delivery_date ? $order->delivery_date->format('d.m.Y') : 'N/A' }}</td>
                            <td>{{ $order->delivery_type ?? 'N/A' }}</td>
                            <td><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                            <td>{{ $order->items->count() }}</td>
                            <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-end text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="btn btn-outline-primary"
                                       title="{{ __('common.common.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('orders.edit')
                                        @if($order->canBeModified())
                                            <a href="{{ route('orders.edit', $order) }}"
                                               class="btn btn-outline-warning"
                                               title="{{ __('common.common.edit') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                    @endcan
                                    @can('orders.delete')
                                        @if($order->canBeModified())
                                            <form action="{{ route('orders.destroy', $order) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('{{ __('orders.messages.confirm_delete') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-outline-danger"
                                                        title="{{ __('common.common.delete') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">{{ __('orders.no_orders') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
