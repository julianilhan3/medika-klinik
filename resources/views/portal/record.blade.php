@extends('layouts.portal')
@section('title', 'Detail Pemeriksaan')
@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <nav class="flex items-center gap-2 text-xs">
        <a href="{{ route('portal.records') }}" class="text-brand-600 hover:underline">Riwayat Medis</a>
        <x-icon name="lucide:chevron-right" class="text-slate-400" /><span class="text-slate-600">Detail Pemeriksaan</span>
    </nav>
    <div>
        <h1 class="text-2xl font-bold">Detail Pemeriksaan</h1>
        <p class="text-sm text-slate-600">Ringkasan hasil pemeriksaan dan obat yang diresepkan dokter.</p>
    </div>

    @include('partials.patient-strip')

    <x-card class="space-y-6 p-5 sm:p-6">
        <div class="grid items-center gap-3 border-b border-slate-100 pb-5 sm:grid-cols-[1.4fr_1.4fr_auto]">
            <p class="text-lg font-semibold">{{ $v['date_label'] }}</p>
            <div><p class="text-sm font-semibold">{{ $v['doctor'] }}</p><p class="text-xs text-slate-500">{{ $v['poli'] }}</p></div>
            <span class="inline-flex w-fit items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><x-icon name="lucide:circle-check" /> Selesai</span>
        </div>

        <section>
            <h2 class="font-semibold">Tanda Vital</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                @foreach ([['lucide:ruler', 'Tinggi badan (TB)', $v['height'], 'cm'], ['lucide:weight', 'Berat badan (BB)', $v['weight'], 'kg'], ['lucide:heart-pulse', 'Tekanan Darah', $v['bp'], 'mmHg']] as [$icon, $label, $val, $unit])
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon :name="$icon" class="text-brand-600" /> {{ $label }}</p>
                        <p class="mt-2 text-2xl font-bold">{{ $val }} <span class="text-xs font-normal text-slate-500">{{ $unit }}</span></p>
                    </div>
                @endforeach
            </div>
        </section>

        <section><h2 class="font-semibold">Keluhan</h2><p class="mt-1 text-sm text-slate-600">{{ $v['complaint'] }}</p></section>
        <section><h2 class="font-semibold">Hasil Pemeriksaan</h2><p class="mt-1 text-sm text-slate-600">{{ $v['findings'] }}</p></section>

        <section>
            <h2 class="font-semibold">Diagnosis</h2>
            <div class="mt-2 space-y-2">
                @foreach ($v['diagnoses'] as $d)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-brand-50 px-4 py-2.5 text-sm">
                        <span class="flex items-center gap-2"><x-icon name="lucide:clipboard-check" class="text-brand-600" /> {{ $d['name'] }}</span>
                        <span class="text-xs font-semibold text-brand-600">{{ $d['code'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <div class="flex items-center justify-between"><h2 class="font-semibold">Daftar Resep</h2><span class="text-xs text-slate-500">{{ count($v['meds']) }} obat</span></div>
            <div class="mt-2 space-y-2">
                @forelse ($v['meds'] as $m)
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="lucide:pill" /></span>
                        <div class="min-w-0 flex-1"><p class="text-sm font-semibold">{{ $m['name'] }}</p><p class="text-xs text-slate-500">{{ $m['rule'] }}</p></div>
                        <span class="shrink-0 text-xs font-medium">{{ $m['qty'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada obat yang diresepkan pada kunjungan ini.</p>
                @endforelse
            </div>
            @if (!empty($v['note']))<p class="mt-3 flex items-start gap-2 text-xs text-slate-600"><x-icon name="lucide:info" class="mt-0.5" /> {{ $v['note'] }}</p>@endif
        </section>

        <a href="{{ route('portal.records') }}" class="btn btn-primary w-full"><x-icon name="lucide:arrow-left" /> Kembali</a>
    </x-card>
</div>
@endsection