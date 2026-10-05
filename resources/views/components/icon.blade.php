@props(['name'])
<iconify-icon icon="{{ $name }}" {{ $attributes->merge(['class' => 'inline-block shrink-0 text-xl align-middle']) }}></iconify-icon>
