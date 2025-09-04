@props(['disabled' => false, 'type' => 'text'])

<input {{ $disabled ? 'disabled' : '' }} type="{{ $type }}"
    {!! $attributes->merge(['class' => 'form-control'.($errors->has($attributes->get('name'))?' is-invalid':'')]) !!}>
