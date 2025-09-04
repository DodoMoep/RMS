@props(['active' => false, 'href' => '#', 'icon' => null])
<li class="nav-item">
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'nav-link'.($active ? ' active fw-semibold' : '')]) }}>
        {!! ($icon ? "<i class=\"nav-icon fas fa-$icon\"></i>" : "") !!}
        {{ $slot }}
    </a>
</li>
