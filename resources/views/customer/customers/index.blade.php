<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>{{ __('customers.customers') }}</h4>
        @can('customers.create')
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> {{ __('customers.create') }}
            </a>
        @endcan
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('ok') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- Filters -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('customers.index') }}" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control"
                           placeholder="{{ __('customers.search_placeholder') }}"
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('customers.all_statuses') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                            {{ __('customers.active') }}
                        </option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                            {{ __('customers.inactive') }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> {{ __('common.common.search') }}
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> {{ __('common.common.clear') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            @if($customers->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p>{{ __('customers.no_customers') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('customers.customer_number') }}</th>
                                <th>{{ __('customers.name') }}</th>
                                <th>{{ __('customers.contact_person_name') }}</th>
                                <th>{{ __('customers.email') }}</th>
                                <th>{{ __('customers.phone') }}</th>
                                <th>{{ __('customers.orders_count') }}</th>
                                <th>{{ __('customers.status') }}</th>
                                <th>{{ __('common.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                                <tr>
                                    <td>
                                        <a href="{{ route('customers.show', $customer) }}" class="text-decoration-none">
                                            <strong>{{ $customer->customer_number }}</strong>
                                        </a>
                                    </td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->contact_person_name ?? '-' }}</td>
                                    <td>{{ $customer->email ?? '-' }}</td>
                                    <td>{{ $customer->phone ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $customer->orders_count }}</span>
                                    </td>
                                    <td>
                                        @if($customer->is_active)
                                            <span class="badge bg-success">{{ __('customers.active') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('customers.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-primary" title="{{ __('customers.actions.view') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('customers.edit')
                                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-warning" title="{{ __('customers.actions.edit') }}">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('customers.delete')
                                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('customers.messages.confirm_delete') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="{{ __('customers.actions.delete') }}" {{ $customer->orders_count > 0 ? 'disabled' : '' }}>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
