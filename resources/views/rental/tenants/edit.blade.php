<x-app-layout>
    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="card shadow-sm"><div class="card-body">
                    <form method="post" action="{{ route('tenants.update',$tenant) }}">
                        @method('PUT')
                        @include('rental.tenants._form', ['tenant'=>$tenant])
                    </form>
                </div></div>
        </div>
    </div>
</x-app-layout>
