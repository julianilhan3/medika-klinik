{{-- Status antrean langsung. Variabel: $t (tiket), $live (queueStatus) --}}
@php
    /** @var array|null $live */
    $live = $live ?? null;
    $progress = max(0, min(100, (int) ($live['progress'] ?? 0)));
    $eta = (string) ($live['eta'] ?? '-');
    $ahead = (int) ($live['ahead'] ?? 0);
@endphp

@if ($live)
<div x-data="{ w: 0 }" x-init="setTimeout(() => w = {{ $progress }}, 150)"
     class="rounded-xl bg-brand-50/70 p-4 ring-1 ring-brand-600/10">
    <div class="flex items-center justify-between text-sm">
        <span class="flex items-center gap-2 text-slate-600">
            <span class="relative flex size-2.5">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-600 opacity-60"></span>
                <span class="relative inline-flex size-2.5 rounded-full bg-brand-600"></span>
            </span>
            Sedang dilayani
        </span>
        <b class="text-base text-brand-700">{{ $live['serving'] ?? '-' }}</b>
    </div>

    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
        <div class="h-2 rounded-full bg-brand-600 transition-all duration-1000 ease-out" :style="'width:' + w + '%'"></div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-sm">
        <span class="inline-flex items-center gap-1.5 text-slate-600">
            <x-icon name="lucide:users" /> {{ $ahead }} antrean di depan Anda
        </span>
        <span class="inline-flex items-center gap-1.5 font-medium text-slate-800">
            <x-icon name="lucide:clock" class="text-brand-600" />
            Estimasi {{ \Illuminate\Support\Str::startsWith($eta, 'sekitar') ? '' : 'sekitar ' }}{{ $eta }}
        </span>
    </div>
</div>
@endif