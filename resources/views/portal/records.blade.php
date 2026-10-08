@extends('layouts.portal')
@section('title', 'Riwayat Medis')
@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Riwayat Medis</p>
        <h1 class="text-2xl font-bold">Riwayat Pemeriksaan</h1>
        <p class="text-sm text-slate-600">
            {{ $error || $rows->isEmpty() ? 'Semua hasil pemeriksaan Anda akan tersimpan di sini.' : 'Lihat kembali hasil pemeriksaan dan resep dari setiap kunjungan Anda.' }}
        </p>
    </div>

    @include('partials.patient-strip')

    @if ($error)
        {{-- Gagal memuat --}}
        <div class="flex items-start justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <div class="flex items-start gap-2"><x-icon name="lucide:alert-circle" class="mt-0.5" />
                <div><p class="font-semibold">Riwayat pemeriksaan tidak dapat dimuat.</p><p class="text-xs">Periksa koneksi internet Anda, lalu coba lagi.</p></div>
            </div>
            <a href="{{ route('portal.records') }}" class="shrink-0 text-xs font-semibold underline">Coba Lagi</a>
        </div>

    @elseif ($rows->isEmpty())
        {{-- Kosong --}}
        <x-card class="px-6 py-12 text-center" x-data="{ img: true }">
            {{-- Perbaikan: Mengganti @error menjadi x-on:error --}}
            <img x-show="img" x-on:error="img = false" src="{{ asset('images/empty-riwayat.png') }}" alt="" class="mx-auto h-32 object-contain">
            
            <span x-show="!img" x-cloak class="mx-auto grid size-20 place-items-center rounded-full bg-brand-50 text-brand-600"><x-icon name="lucide:clipboard-list" class="text-4xl" /></span>
            <h2 class="mt-5 text-lg font-bold">Belum ada riwayat pemeriksaan</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-600">Riwayat akan muncul setelah Anda diperiksa dan dokter mencatat hasil pemeriksaan.</p>
            <a href="#" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:underline"><x-icon name="lucide:help-circle" /> Sudah diperiksa? Hubungi klinik</a>
        </x-card>

    @else
        <div class="flex items-center justify-between text-sm">
            <p class="font-semibold">{{ $rows->count() }} kunjungan</p>
            <a href="{{ route('portal.records', ['sort' => $sort === 'desc' ? 'asc' : 'desc']) }}" class="flex items-center gap-1 text-slate-500 hover:text-brand-600">
                <x-icon :name="$sort === 'desc' ? 'lucide:arrow-down-wide-narrow' : 'lucide:arrow-up-narrow-wide'" /> {{ $sort === 'desc' ? 'Terbaru lebih dulu' : 'Terlama lebih dulu' }}
            </a>
        </div>

        @foreach ($rows as $i => $r)
            <x-card class="space-y-3 p-4 transition hover:shadow-md" x-data="{ s: false }" x-init="setTimeout(() => s = true, {{ $i * 70 }})" x-bind:class="s ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'">
                <div class="grid items-center gap-3 sm:grid-cols-[1.4fr_1.4fr_auto]">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-600"><x-icon name="lucide:calendar" /></span>
                      <div><p class="font-semibold">{{ $r['date_label'] }}</p><p class="text-xs text-slate-500">{{ $r['time'] }} WIB</p></div>
                    </div>
                    <div><p class="text-sm font-medium">{{ $r['doctor'] }}</p><p class="text-xs text-slate-500">{{ $r['poli'] }}</p></div>
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"><x-icon name="lucide:check-circle" /> Selesai</span>
                </div>
                <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm"><span class="mr-2 text-xs text-slate-500">Diagnosis</span>{{ $r['diagnosis'] }}</p>
                <a href="{{ route('portal.records.show', $r['id']) }}" class="btn btn-primary w-full">Lihat Detail <x-icon name="lucide:arrow-right" /></a>
            </x-card>
        @endforeach

        <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:info" /> Riwayat ditampilkan setelah pemeriksaan selesai dan dicatat oleh dokter.</p>
    @endif
</div>
@endsection