<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            @if($contact->type->value === 'tenant')
                <span class="badge bg-info me-2">{{ __('contacts.type_tenant') }}</span>
            @elseif($contact->type->value === 'customer')
                <span class="badge bg-primary me-2">{{ __('contacts.type_customer') }}</span>
            @else
                <span class="badge bg-success me-2">{{ __('contacts.type_both') }}</span>
            @endif
            <h4 class="d-inline mb-0">{{ $contact->name }}</h4>
        </div>
        <div class="d-flex gap-2">
            @can('contacts.edit')
                <a href="{{ route('contacts.edit', $contact) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> {{ __('common.common.edit') }}
                </a>
            @endcan
            <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('contacts.back_to_list') }}
            </a>
        </div>
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title">{{ __('contacts.contact_information') }}</h6>
                    <dl class="row mb-0 small">
                        @if($contact->customer_number)
                            <dt class="col-sm-5">{{ __('contacts.customer_number') }}:</dt>
                            <dd class="col-sm-7">{{ $contact->customer_number }}</dd>
                        @endif
                        <dt class="col-sm-5">{{ __('contacts.name') }}:</dt>
                        <dd class="col-sm-7">{{ $contact->name }}</dd>
                        @if($contact->contact_person_name)
                            <dt class="col-sm-5">{{ __('contacts.contact_person_name') }}:</dt>
                            <dd class="col-sm-7">{{ $contact->contact_person_name }}</dd>
                        @endif
                        @if($contact->email)
                            <dt class="col-sm-5">{{ __('contacts.email') }}:</dt>
                            <dd class="col-sm-7">{{ $contact->email }}</dd>
                        @endif
                        @if($contact->phone)
                            <dt class="col-sm-5">{{ __('contacts.phone') }}:</dt>
                            <dd class="col-sm-7">{{ $contact->phone }}</dd>
                        @endif
                        @if($contact->formatted_address)
                            <dt class="col-sm-5">{{ __('contacts.address') }}:</dt>
                            <dd class="col-sm-7">{!! nl2br(e($contact->formatted_address)) !!}</dd>
                        @endif
                        @if($contact->notes)
                            <dt class="col-sm-5">{{ __('contacts.notes') }}:</dt>
                            <dd class="col-sm-7">{{ $contact->notes }}</dd>
                        @endif
                        <dt class="col-sm-5">{{ __('contacts.status') }}:</dt>
                        <dd class="col-sm-7">
                            @if($contact->is_active)
                                <span class="badge bg-success">{{ __('contacts.active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('contacts.inactive') }}</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if(in_array($contact->type->value, ['customer', 'both']))
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title mb-0">{{ __('contacts.recent_orders') }}</h6>
                    <span class="badge bg-primary">{{ $contact->orders_count ?? 0 }}</span>
                </div>
                @if($contact->orders->isEmpty())
                    <p class="text-muted small mb-0">{{ __('contacts.no_orders') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('orders.order_number') }}</th>
                                    <th>{{ __('orders.delivery_date') }}</th>
                                    <th>{{ __('orders.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contact->orders as $order)
                                    <tr>
                                        <td>{{ $order->order_number }}</td>
                                        <td>{{ $order->delivery_date?->format('d.m.Y') }}</td>
                                        <td><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                                        <td><a href="{{ route('orders.show', $order) }}" class="btn btn-link btn-sm">{{ __('common.common.view') }}</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if(in_array($contact->type->value, ['tenant', 'both']))
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title mb-0">{{ __('contacts.recent_rentals') }}</h6>
                    <span class="badge bg-info">{{ $contact->rentals_count ?? 0 }}</span>
                </div>
                @if($contact->rentals->isEmpty())
                    <p class="text-muted small mb-0">{{ __('contacts.no_rentals') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('rentals.hall') }}</th>
                                    <th>{{ __('rentals.start') }}</th>
                                    <th>{{ __('rentals.end') }}</th>
                                    <th>{{ __('rentals.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contact->rentals as $rental)
                                    <tr>
                                        <td>{{ $rental->hall->name ?? '—' }}</td>
                                        <td>{{ $rental->start?->format('d.m.Y H:i') }}</td>
                                        <td>{{ $rental->end?->format('d.m.Y H:i') }}</td>
                                        <td><span class="badge bg-secondary">{{ $rental->status }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
