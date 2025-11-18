<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-10">
            <div class="card shadow-sm"><div class="card-body">
                    <form method="post" action="{{ route('orders.update', $order) }}">
                        @method('PUT')
                        @include('orders._form')
                    </form>
                </div></div>
        </div>
    </div>
</x-app-layout>
