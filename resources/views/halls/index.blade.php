<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="get" class="d-flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Suche…" class="form-control form-control-sm">
            <button class="btn btn-outline-secondary btn-sm">Suchen</button>
        </form>
        <a href="{{ route('halls.create') }}" class="btn btn-primary btn-sm">Neu</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Addresse</th><th class="text-end">Aktionen</th></tr></thead>
                <tbody>
                @forelse($halls as $h)
                    <tr>
                        <td>{{ $h->name }}</td>
                        <td>{{ $h->address }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('halls.edit',$h) }}" class="btn btn-link btn-sm">Bearbeiten</a>
                            <form action="{{ route('halls.destroy',$h) }}" method="post" class="d-inline" onsubmit="return confirm('Wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm">Löschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Keine Einträge.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $halls->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
