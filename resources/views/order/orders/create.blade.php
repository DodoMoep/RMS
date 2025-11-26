<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-10">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <div class="card shadow-sm"><div class="card-body">
                    <form method="post" action="{{ route('orders.store') }}">
                        @include('order.orders._form')
                    </form>
                </div></div>
        </div>
    </div>
</x-app-layout>
