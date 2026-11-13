<x-app-layout>
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('rentals.create') }}" class="btn btn-primary btn-sm">{{ __('rentals.new') }}</a>
    </div>

    <x-flash />

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>{{ __('rentals.tenant') }}</th><th>{{ __('rentals.hall') }}</th><th>{{ __('rentals.start') }}</th><th>{{ __('rentals.end') }}</th><th>{{ __('rentals.status') }}</th>
                    <th>{{ __('rentals.price') }}</th><th>{{ __('rentals.deposit') }}</th><th class="text-end">{{ __('common.common.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rentals as $r)
                    <tr>
                        <td>{{ $r->contact->name }}</td>
                        <td>{{ $r->hall->name }}</td>
                        <td>{{ $r->start->format('d.m.Y H:i') }}</td>
                        <td>{{ $r->end->format('d.m.Y H:i') }}</td>
                        <td><span class="badge text-bg-secondary">{{ __('rentals.statuses.'.$r->status) }}</span></td>
                        <td>{{ $r->price ? number_format($r->price,2,',','.') : '—' }}</td>
                        <td>{{ $r->deposit ? number_format($r->deposit,2,',','.') : '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('rentals.edit',$r) }}" class="btn btn-link btn-sm">{{ __('common.common.edit') }}</a>
                            <form action="{{ route('rentals.destroy',$r) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('common.messages.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm">{{ __('common.common.delete') }}</button>
                            </form>
                            <form action="{{ route('rentals.handover',$r) }}" method="post" class="d-inline">@csrf
                                <button class="btn btn-link btn-sm">{{ $r->handover ? __('rentals.actions.open_handover') : __('rentals.actions.create_handover') }}</button>
                            </form>
                            <form action="{{ route('rentals.return',$r) }}" method="post" class="d-inline">@csrf
                                <button class="btn btn-link btn-sm">{{ $r->returnProtocol ? __('rentals.actions.open_return') : __('rentals.actions.create_return') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">{{ __('common.messages.no_entries') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $rentals->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
