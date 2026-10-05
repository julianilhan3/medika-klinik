@extends('layouts.portal')
@section('title', 'Detail Resep')
@php
    $stages = [['Menunggu', 'Resep diterima'], ['Diproses', 'Obat disiapkan'], ['Siap', 'Siap Diambil'], ['Diambil', 'Obat diambil']];
    $idx = collect($stages)->search(fn ($s) => $s[0] === $rx['status']);
    $idx = $idx === false ? 0 : $idx;
    $sub = ['Resep diterima', 'Obat disiapkan', 'Tahap saat ini', 'Belum diambil'];
@endphp
@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <nav class="flex items-center gap-2 text-xs text-brand-600">
        <a href="{{ route('portal.prescriptions') }}" class="hover:underline">Status Obat</a><x-icon name="lucide:chevron-right" class="text-slate-400" />
        <a href="{{ route('portal.prescriptions') }}" class="hover:underline">Daftar Resep</a><x-icon name="lucide:chevron-right" class="text-slate-400" />
        <span class="text-slate-600">Detail Resep</span>
    </nav>
    <div><h1 class="text-2xl font-bold">Detail Resep</h1><p class="text-sm text-slate-600">Lihat status penyiapan, rincian obat, dan petunjuk pengambilan resep Anda.</p></div>

    <x-card class="space-y-6 p-5 sm:p-6">
        <h2 class="font-semibold">Proses penyiapan obat</h2>
        <ol class="grid grid-cols-4">
            @foreach ($stages as $i => [$key, $title])
                @php $done = $i < $idx; $now = $i === $idx; @endphp
                <li class="relative text-center">
                    @unless ($loop->first)<span class="absolute right-1/2 top-4 h-0.5 w-full {{ $i <= $idx ? 'bg-brand-600' : 'bg-slate-200' }}"></span>@endunless
                    <span class="relative z-10 mx-auto grid size-8 place-items-center rounded-full text-sm font-semibold {{ $now ? 'bg-brand-600 text-white' : ($done ? 'bg-brand-50 text-brand-600' : 'bg-slate-100 text-slate-500') }}">
                        @if ($done) <x-icon name="lucide:check" /> @else {{ $i + 1 }} @endif
                    </span>
                    <p class="mt-2 text-xs font-semibold sm:text-sm {{ $now ? 'text-brand-600' : '' }}">{{ $title }}</p>
                    <p class="hidden text-xs sm:block {{ $now ? 'text-brand-600' : 'text-slate-500' }}">{{ $now ? 'Tahap saat ini' : ($done ? $sub[$i] : $sub[$i]) }}</p>
                </li>
            @endforeach
        </ol>

        @if ($rx['status'] === 'Siap')
            <p class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800"><x-icon name="lucide:circle-check" /> Obat Anda sudah siap, silakan ambil di loket apotek</p>
        @endif

        <div class="grid items-center gap-3 border-b border-slate-100 pb-5 sm:grid-cols-[1fr_1fr_auto]">
            <div><p class="text-lg font-semibold">{{ $rx['date'] }}</p><p class="text-xs text-slate-500">{{ $rx['code'] }} &bull; {{ $rx['time'] }} WIB</p></div>
            <div><p class="text-sm font-semibold">{{ $rx['doctor'] }}</p><p class="text-xs text-slate-500">{{ $rx['poli'] }}</p></div>
            <x-badge :text="$rx['status'] === 'Siap' ? 'Siap Diambil' : $rx['status']" :tone="$rx['status'] === 'Siap' ? 'green' : null" />
        </div>

        <div>
            <div class="mb-2 flex justify-between"><h3 class="font-semibold">Obat dalam resep</h3><span class="text-xs text-slate-500">{{ count($rx['items']) }} obat</span></div>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[34rem] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold"><tr><th class="p-3">Nama Obat</th><th class="p-3">Dosis</th><th class="p-3">Jumlah</th><th class="p-3">Aturan Pakai</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach ($rx['items'] as $m)
                        <tr><td class="p-3"><p class="font-semibold">{{ $m['name'] }}</p><p class="text-xs text-slate-500">{{ $m['form'] }}</p></td><td class="p-3">{{ $m['dose'] }}</td><td class="p-3">{{ $m['qty'] }}</td><td class="p-3">{{ $m['rule'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if (!empty($rx['note']))<p class="mt-3 flex items-start gap-2 text-xs text-slate-600"><x-icon name="lucide:info" class="mt-0.5" /> {{ $rx['note'] }}</p>@endif
        </div>

        <div class="rounded-xl bg-slate-50 p-4 text-sm">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="lucide:map-pin" class="text-brand-600" /> Pengambilan di loket apotek</p>
            <p class="mt-1 text-slate-600">Tunjukkan nomor resep {{ $rx['code'] }} dan kartu pasien kepada petugas apotek. Petugas akan menjelaskan cara penggunaan obat sebelum Anda pulang.</p>
        </div>

        <a href="{{ route('portal.prescriptions') }}" class="btn btn-primary w-full"><x-icon name="lucide:arrow-left" /> Kembali ke Daftar Resep</a>
    </x-card>
</div>
@endsection