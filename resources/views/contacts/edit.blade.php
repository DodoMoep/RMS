<x-app-layout>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">{{ __('contacts.edit') }}</h5>
            <form action="{{ route('contacts.update', $contact) }}" method="POST">
                @method('PUT')
                @include('contacts._form')
            </form>
        </div>
    </div>
</x-app-layout>
