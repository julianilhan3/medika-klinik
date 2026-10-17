<x-card class="p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-full bg-brand-50 text-brand-600"><x-icon name="lucide:user-round" class="text-2xl" /></span>
            <div><p class="text-lg font-bold">{{ $p['patient'] }}</p><p class="text-sm text-slate-500">{{ $p['gender'] }} - {{ $p['age'] }} tahun - {{ $p['rm'] }}</p></div></div>
        <div class="text-right text-sm"><p class="text-xs text-slate-500">Antrean {{ 'Poli Umum' }}</p><p class="text-lg font-bold">{{ $p['no'] }}</p></div>
    </div>
    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
        <div><dt class="text-xs text-slate-500">Tanggal kunjungan</dt><dd class="font-semibold">{{ now()->translatedFormat('j F Y') }} - {{ $p['registered'] }} WIB</dd></div>
        <div>
    <dt class="text-xs text-slate-500">Dokter pemeriksa</dt>
    <dd class="font-semibold">
        {{ session('staff.name') ?? auth()->user()?->name ?? 'Dokter belum teridentifikasi' }}
    </dd>
</div>
        
@php
    $allergies = $p['allergies'] ?? '';
    $allergyText = is_array($allergies)
        ? implode(', ', $allergies)
        : trim((string) $allergies);
@endphp

<div>
    <dt class="text-xs text-slate-500">Riwayat alergi</dt>
    <dd class="font-semibold {{ $allergyText !== '' ? 'text-red-600' : 'text-amber-700' }}">
        @if ($allergyText !== '')
            {{ $allergyText }}
            - {{ strtolower($p['allergy_reaction'] ?? 'reaksi belum diketahui') }}
        @else
            Informasi alergi belum tersedia
        @endif
    </dd>
</div>

    </dl>
</x-card>
