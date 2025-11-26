<x-app-layout>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="get" class="d-flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('common.common.search') }}…" class="form-control form-control-sm">
            <button class="btn btn-outline-secondary btn-sm">{{ __('common.common.search') }}</button>
        </form>
        <a href="{{ route('articles.create') }}" class="btn btn-primary btn-sm">{{ __('common.common.create') }}</a>
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
                        <th>{{ __('articles.name') }}</th>
                        <th>{{ __('articles.sku') }}</th>
                        <th>{{ __('articles.price') }}</th>
                        <th>{{ __('articles.status') }}</th>
                        <th class="text-end">{{ __('common.common.actions') }}</th>
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
                                @if($article->is_active)
                                    <span class="badge bg-success">{{ __('articles.active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('articles.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('articles.edit', $article) }}"
                                       class="btn btn-outline-warning"
                                       title="{{ __('common.common.edit') }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('articles.destroy', $article) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ __('common.messages.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-outline-danger"
                                                title="{{ __('common.common.delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">{{ __('articles.no_articles') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $articles->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
