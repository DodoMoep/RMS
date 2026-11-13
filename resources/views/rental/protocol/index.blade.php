<x-app-layout>
    <x-flash />

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr><th>{{ __('protocols.list.type') }}</th><th>{{ __('protocols.list.tenant') }}</th><th>{{ __('protocols.list.hall') }}</th><th>{{ __('protocols.list.created') }}</th><th>{{ __('protocols.list.pdf') }}</th><th class="text-end">{{ __('protocols.list.action') }}</th></tr>
                </thead>
                <tbody>
                @forelse($protocols as $p)
                    <tr>
                        <td>{{ __('protocols.types.'.$p->type) }}</td>
                        <td>{{ $p->rental->contact->name }}</td>
                        <td>{{ $p->rental->hall->name }}</td>
                        <td>{{ $p->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($p->pdf_path)
                                <span class="text-success">{{ __('protocols.list.pdf_available') }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-link btn-sm" href="{{ route('protocol.form',$p) }}">{{ __('protocols.list.open') }}</a>
                            @if($p->pdf_path)
                                <a class="btn btn-link btn-sm" href="{{ route('protocol.pdf',$p) }}" target="_blank">{{ __('protocols.list.pdf') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">{{ __('common.messages.no_entries') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $protocols->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
