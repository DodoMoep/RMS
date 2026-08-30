<x-app-layout>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">{{ __('contacts.create') }}</h5>
            <form action="{{ route('contacts.store') }}" method="POST">
                @php $contact = null; @endphp
                @include('contacts._form')
            </form>
        </div>
    </div>
</x-app-layout>
