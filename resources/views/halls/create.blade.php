<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h5 mb-3">Neue Halle</h1>
                    <form method="post" action="{{ route('halls.store') }}">
                        @include('halls._form', ['hall' => new \App\Models\Hall])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
