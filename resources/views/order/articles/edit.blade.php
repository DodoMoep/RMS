<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="card shadow-sm"><div class="card-body">
                    <form method="post" action="{{ route('articles.update', $article) }}">
                        @method('PUT')
                        @include('order.articles._form')
                    </form>
                    <div class="mt-4 pt-3 border-top">
                        <small class="text-muted">
                            <div><strong>{{ __('common.dates.created_at') }}:</strong> {{ $article->created_at->format('d.m.Y H:i') }}</div>
                            <div><strong>{{ __('common.dates.updated_at') }}:</strong> {{ $article->updated_at->format('d.m.Y H:i') }}</div>
                        </small>
                    </div>
                </div></div>
        </div>
    </div>
</x-app-layout>
