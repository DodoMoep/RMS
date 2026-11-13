{{-- Meldungen nach einer Aktion: Erfolg, Fehler, Status und allgemeine Fehler ohne Feldbezug.
     Feldfehler (benannte Schluessel) zeigt x-input-error am Feld, sie erscheinen hier nicht doppelt. --}}
@php
    $generalErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => is_int($key))
        ->flatten();
@endphp

@if(session('ok'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('ok') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
@endif

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
@endif

@if($generalErrors->isNotEmpty())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach($generalErrors as $message)
            <div>{{ $message }}</div>
        @endforeach
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
@endif
