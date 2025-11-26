<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>{{ __('customers.customer') }}: {{ $customer->customer_number }}</h4>
        <div>
            @can('customers.edit')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> {{ __('customers.actions.edit') }}
                </a>
            @endcan
            @can('customers.toggle-status')
                <form action="{{ route('customers.toggle-status', $customer) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-{{ $customer->is_active ? 'secondary' : 'success' }}">
                        <i class="fas fa-{{ $customer->is_active ? 'ban' : 'check' }}"></i>
                        {{ $customer->is_active ? __('customers.actions.deactivate') : __('customers.actions.activate') }}
                    </button>
                </form>
            @endcan
            <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('customers.back_to_list') }}
            </a>
        </div>
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
        </div>
    @endif

    <div class="row g-3">
        <!-- Customer Information -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('customers.customer_information') }}</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('customers.customer_number') }}:</dt>
                        <dd class="col-sm-8"><strong>{{ $customer->customer_number }}</strong></dd>

                        <dt class="col-sm-4">{{ __('customers.name') }}:</dt>
                        <dd class="col-sm-8">{{ $customer->name }}</dd>

                        @if($customer->contact_person_name)
                            <dt class="col-sm-4">{{ __('customers.contact_person_name') }}:</dt>
                            <dd class="col-sm-8">{{ $customer->contact_person_name }}</dd>
                        @endif

                        @if($customer->email)
                            <dt class="col-sm-4">{{ __('customers.email') }}:</dt>
                            <dd class="col-sm-8">
                                <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                            </dd>
                        @endif

                        @if($customer->phone)
                            <dt class="col-sm-4">{{ __('customers.phone') }}:</dt>
                            <dd class="col-sm-8">
                                <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                            </dd>
                        @endif

                        @if($customer->street || $customer->city)
                            <dt class="col-sm-4">{{ __('customers.address') }}:</dt>
                            <dd class="col-sm-8">
                                @if($customer->street)
                                    {{ $customer->street }}<br>
                                @endif
                                @if($customer->zip_code || $customer->city)
                                    {{ $customer->zip_code }} {{ $customer->city }}<br>
                                @endif
                                @if($customer->address_notes)
                                    <small class="text-muted">{{ $customer->address_notes }}</small>
                                @endif
                            </dd>
                        @endif

                        <dt class="col-sm-4">{{ __('customers.status') }}:</dt>
                        <dd class="col-sm-8">
                            @if($customer->is_active)
                                <span class="badge bg-success">{{ __('customers.active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('customers.inactive') }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">{{ __('customers.created_at') }}:</dt>
                        <dd class="col-sm-8">{{ $customer->created_at->format('d.m.Y H:i') }}</dd>

                        <dt class="col-sm-4">{{ __('customers.updated_at') }}:</dt>
                        <dd class="col-sm-8">{{ $customer->updated_at->format('d.m.Y H:i') }}</dd>
                    </dl>

                    @if($customer->notes)
                        <div class="mt-3">
                            <strong>{{ __('customers.notes') }}:</strong>
                            <p class="text-muted mb-0">{{ $customer->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('customers.statistics') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <div class="display-6 text-primary">{{ $customer->orders_count }}</div>
                                <small class="text-muted">{{ __('customers.total_orders') }}</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 text-center">
                                <div class="display-6 text-success">
                                    {{ $customer->orders()->where('status', 'delivered')->count() }}
                                </div>
                                <small class="text-muted">{{ __('customers.delivered_orders') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('customers.recent_orders') }}</h6>
                    @can('orders.create')
                        <a href="{{ route('orders.create') }}?customer_id={{ $customer->id }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> {{ __('customers.create_order') }}
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    @if($customer->orders->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p>{{ __('customers.no_orders') }}</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('orders.order_number') }}</th>
                                        <th>{{ __('orders.status') }}</th>
                                        <th>{{ __('orders.items') }}</th>
                                        <th>{{ __('orders.created_at') }}</th>
                                        <th>{{ __('common.common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->orders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}">
                                                    {{ $order->order_number }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge {{ $order->status->color() }}">
                                                    {{ $order->status->label() }}
                                                </span>
                                            </td>
                                            <td>{{ $order->items_count }} {{ __('orders.items') }}</td>
                                            <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                            <td>
                                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($customer->orders_count > 10)
                            <div class="text-center mt-3">
                                <a href="{{ route('orders.index') }}?search={{ $customer->customer_number }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('customers.view_all_orders') }}
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
