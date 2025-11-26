<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('customers.create') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('customers.store') }}">
                        @include('customer.customers._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
