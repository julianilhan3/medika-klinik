@props(['name', 'size' => 'size-14'])
@php
    $url = \App\Support\DoctorPhoto::url($name);
    $ini = mb_substr(preg_replace('/^(drg|dr)\.?\s*/i', '', $name), 0, 1);
@endphp
@if ($url)
    <img src="{{ $url }}" alt="{{ $name }}" loading="lazy" {{ $attributes->class([$size, 'shrink-0 rounded-xl object-cover']) }}>
@else
    <span {{ $attributes->class([$size, 'grid shrink-0 place-items-center rounded-xl bg-brand-50 font-semibold text-brand-600']) }}>{{ $ini }}</span>
@endif