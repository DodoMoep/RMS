<x-app-layout>
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('rentals.create') }}" class="btn btn-primary btn-sm">Neu</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Mieter:in</th><th>Halle</th><th>Start</th><th>Ende</th><th>Status</th>
                    <th>Preis</th><th>Kaution</th><th class="text-end">Aktionen</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rentals as $r)
                    <tr>
                        <td>{{ $r->contact->name }}</td>
                        <td>{{ $r->hall->name }}</td>
                        <td>{{ $r->start->format('d.m.Y H:i') }}</td>
                        <td>{{ $r->end->format('d.m.Y H:i') }}</td>
                        <td><span class="badge text-bg-secondary">{{ $r->status }}</span></td>
                        <td>{{ $r->price ? number_format($r->price,2,',','.') : '—' }}</td>
                        <td>{{ $r->deposit ? number_format($r->deposit,2,',','.') : '—' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('rentals.edit',$r) }}" class="btn btn-link btn-sm">Bearbeiten</a>
                            <form action="{{ route('rentals.destroy',$r) }}" method="post" class="d-inline" onsubmit="return confirm('Wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm">Löschen</button>
                            </form>
                            <form action="{{ route('rentals.handover',$r) }}" method="post" class="d-inline">@csrf
                                <button class="btn btn-link btn-sm">{{ $r->handover ? 'Übergabe öffnen' : 'Übergabe anlegen' }}</button>
                            </form>
                            <form action="{{ route('rentals.return',$r) }}" method="post" class="d-inline">@csrf
                                <button class="btn btn-link btn-sm">{{ $r->returnProtocol ? 'Rücknahme öffnen' : 'Rücknahme anlegen' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">Keine Einträge.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $rentals->links('pagination::bootstrap-5') }}</div>
</x-app-layout>
