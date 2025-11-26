<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h5 mb-3">Halle bearbeiten</h1>
                    <form method="post" action="{{ route('halls.update',$hall) }}">
                        @method('PUT')
                        @include('rental.halls._form', ['hall' => $hall])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
