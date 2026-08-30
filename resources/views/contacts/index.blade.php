<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="get" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('contacts.search_placeholder') }}"
                   class="form-control form-control-sm" style="width:220px;">
            <select name="type" class="form-select form-select-sm" style="width:150px;">
                <option value="">{{ __('contacts.all_types') }}</option>
                <option value="tenant"   {{ request('type') === 'tenant'   ? 'selected' : '' }}>{{ __('contacts.type_tenant') }}</option>
                <option value="customer" {{ request('type') === 'customer' ? 'selected' : '' }}>{{ __('contacts.type_customer') }}</option>
                <option value="both"     {{ request('type') === 'both'     ? 'selected' : '' }}>{{ __('contacts.type_both') }}</option>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:130px;">
                <option value="">{{ __('contacts.all_statuses') }}</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>{{ __('contacts.active') }}</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('contacts.inactive') }}</option>
            </select>
            <button class="btn btn-outline-secondary btn-sm">{{ __('common.common.filter') }}</button>
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('common.common.reset') }}</a>
        </form>
        @can('contacts.create')
            <a href="{{ route('contacts.create') }}" class="btn btn-primary btn-sm">{{ __('common.common.create') }}</a>
        @endcan
    </div>

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('contacts.type') }}</th>
                        <th>{{ __('contacts.customer_number') }}</th>
                        <th>{{ __('contacts.name') }}</th>
                        <th>{{ __('contacts.email') }}</th>
                        <th>{{ __('contacts.phone') }}</th>
                        <th>{{ __('contacts.city') }}</th>
                        <th>{{ __('contacts.orders_count') }}</th>
                        <th>{{ __('contacts.rentals_count') }}</th>
                        <th>{{ __('contacts.status') }}</th>
                        <th class="text-end">{{ __('common.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr>
                            <td>
                                @if($contact->type->value === 'tenant')
                                    <span class="badge bg-info">{{ __('contacts.type_tenant') }}</span>
                                @elseif($contact->type->value === 'customer')
                                    <span class="badge bg-primary">{{ __('contacts.type_customer') }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('contacts.type_both') }}</span>
                                @endif
                            </td>
                            <td>{{ $contact->customer_number ?? '—' }}</td>
                            <td>
                                <a href="{{ route('contacts.show', $contact) }}" class="text-decoration-none fw-semibold">
                                    {{ $contact->name }}
                                </a>
                            </td>
                            <td>{{ $contact->email ?? '—' }}</td>
                            <td>{{ $contact->phone ?? '—' }}</td>
                            <td>{{ $contact->city ?? '—' }}</td>
                            <td>
                                @if(in_array($contact->type->value, ['customer', 'both']))
                                    {{ $contact->orders_count }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(in_array($contact->type->value, ['tenant', 'both']))
                                    {{ $contact->rentals_count }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($contact->is_active)
                                    <span class="badge bg-success">{{ __('contacts.active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('contacts.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    @can('contacts.view')
                                        <a href="{{ route('contacts.show', $contact) }}"
                                           class="btn btn-outline-primary"
                                           title="{{ __('common.common.view') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endcan
                                    @can('contacts.edit')
                                        <a href="{{ route('contacts.edit', $contact) }}"
                                           class="btn btn-outline-warning"
                                           title="{{ __('common.common.edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('contacts.toggle-status')
                                        <form action="{{ route('contacts.toggle-status', $contact) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-outline-secondary"
                                                    title="{{ $contact->is_active ? __('contacts.actions.deactivate') : __('contacts.actions.activate') }}">
                                                <i class="fas fa-toggle-{{ $contact->is_active ? 'on' : 'off' }}"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @can('contacts.delete')
                                        <form action="{{ route('contacts.destroy', $contact) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('{{ __('contacts.messages.confirm_delete') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-outline-danger"
                                                    title="{{ __('common.common.delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted">{{ __('contacts.no_contacts') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $contacts->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
