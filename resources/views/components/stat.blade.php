@props(['label', 'value', 'hint' => null, 'icon' => 'lucide:activity', 'tone' => 'brand'])
@php $t = ['brand' => 'bg-brand-50 text-brand-600', 'amber' => 'bg-amber-50 text-amber-600', 'green' => 'bg-emerald-50 text-emerald-600', 'red' => 'bg-red-50 text-red-600', 'blue' => 'bg-sky-50 text-sky-600'][$tone]; @endphp
<x-card class="p-5">
    <div class="flex items-start justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        <span class="grid size-9 place-items-center rounded-lg {{ $t }}"><x-icon :name="$icon" /></span>
    </div>
    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $value }}</p>
    @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
</x-card>
