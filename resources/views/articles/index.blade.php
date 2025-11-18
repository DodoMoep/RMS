<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="get" class="d-flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Suche…" class="form-control form-control-sm">
            <button class="btn btn-outline-secondary btn-sm">Suchen</button>
        </form>
        <a href="{{ route('articles.create') }}" class="btn btn-primary btn-sm">Neu</a>
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
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Preis</th>
                        <th>Lager</th>
                        <th>Status</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $article->name }}</div>
                                @if($article->description)
                                    <small class="text-muted">{{ Str::limit($article->description, 50) }}</small>
                                @endif
                            </td>
                            <td>{{ $article->sku ?? '-' }}</td>
                            <td>
                                @if($article->price)
                                    {{ number_format($article->price, 2, ',', '.') }} €
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $article->stock > 10 ? 'bg-success' : ($article->stock > 0 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ $article->stock }}
                                </span>
                            </td>
                            <td>
                                @if($article->is_active)
                                    <span class="badge bg-success">Aktiv</span>
                                @else
                                    <span class="badge bg-secondary">Inaktiv</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('articles.edit', $article) }}" class="btn btn-link btn-sm">Bearbeiten</a>
                                <form action="{{ route('articles.destroy', $article) }}" method="post" class="d-inline" onsubmit="return confirm('Wirklich löschen?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-link text-danger btn-sm">Löschen</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">Keine Artikel vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $articles->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
