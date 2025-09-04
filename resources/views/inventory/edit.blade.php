<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h5 mb-3">Inventar-Position bearbeiten</h1>
                    <form method="post" action="{{ route('inventory-items.update',$item) }}">
                        @method('PUT')
                        @include('inventory._form', ['item' => $item])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
