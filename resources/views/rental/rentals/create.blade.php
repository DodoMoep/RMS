<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h5 mb-3">Neue Vermietung</h1>
                    <form method="post" action="{{ route('rentals.store') }}">
                        @include('rental.rentals._form', ['rental' => new \App\Models\Rental\Rental])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
