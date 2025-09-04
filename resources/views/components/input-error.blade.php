@props(['messages' => []])
@if ($messages)
    <div class="invalid-feedback d-block">
        @foreach ((array) $messages as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif
