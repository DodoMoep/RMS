<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h5 mb-3">Vermietung bearbeiten</h1>
                    <form method="post" action="{{ route('rentals.update',$rental) }}">
                        @method('PUT')
                        @include('rental.rentals._form', ['rental' => $rental])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
