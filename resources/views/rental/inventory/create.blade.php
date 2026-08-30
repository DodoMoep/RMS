<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h5 mb-3">Neue Inventar-Position</h1>
                    <form method="post" action="{{ route('inventory-items.store') }}">
                        @include('rental.inventory._form', ['item' => new \App\Models\Rental\InventoryItem])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
