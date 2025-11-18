<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="card shadow-sm"><div class="card-body">
                    <form method="post" action="{{ route('articles.store') }}">
                        @include('articles._form')
                    </form>
                </div></div>
        </div>
    </div>
</x-app-layout>
