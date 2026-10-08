@extends('layouts.portal')
@section('title', 'Booking Berhasil')
@section('content')
@php 
    $p = session('patient'); 
@endphp

{{-- CSS KHUSUS CETAK YANG DIPERBARUI --}}
<style>
    @media print {
        /* 1. Hilangkan teks URL, tanggal, dan nomor halaman (1/2) dari browser */
        @page {
            margin: 0;
        }

        /* 2. Kunci tinggi halaman menjadi 1 layar saja dan hilangkan scroll */
        html, body {
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        /* 3. Sembunyikan semua elemen default */
        body * {
            visibility: hidden;
        }
        
        /* 4. Munculkan HANYA kotak tiket */
        #print-section, #print-section * {
            visibility: visible;
        }
        
        /* 5. Tarik posisi tiket ke atas kertas agar pas */
        #print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 2cm; /* Jarak batas tepi kertas */
            margin: 0;
        }
    }
</style>

<div class="mx-auto max-w-4xl">
    {{-- Judul & Header --}}
    <div class="mb-6">
        <a href="{{ route('portal.home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-brand-600">
            <x-icon name="lucide:chevron-left" /> Kembali
        </a>
        <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Booking Online</h1>
                <p class="mt-1 text-sm text-slate-600">Kunjungan Anda sudah terjadwal. Kami siap menyambut Anda di klinik.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-600">
                <x-icon name="lucide:check-circle" class="text-emerald-500" /> Selesai
            </span>
        </div>
    </div>

    {{-- Stepper Indikator Selesai --}}
    <ol class="mb-6 flex items-center gap-2 px-1 sm:gap-3">
        @foreach (['Pilih Jadwal', 'Data Pasien', 'Konfirmasi'] as $label)
            <li class="flex items-center gap-2 sm:gap-3 {{ $loop->last ? '' : 'flex-1' }}">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600 transition sm:size-10">
                    <x-icon name="lucide:check" />
                </span>
                <span class="hidden text-sm font-medium text-emerald-600 sm:inline">{{ $label }}</span>
                @unless ($loop->last)<span class="h-px flex-1 bg-emerald-200 transition"></span>@endunless
            </li>
        @endforeach
    </ol>

    {{-- Kartu Utama --}}
    <x-card class="p-5 sm:p-10">
        
        <div class="text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-50 text-emerald-500">
                <x-icon name="lucide:check" class="text-3xl" />
            </span>
            <h2 class="mt-5 text-2xl font-bold text-slate-900">Booking Berhasil!</h2>
            <p class="mt-2 text-sm text-slate-600">Terima kasih, {{ $p['name'] ?? 'Pasien' }}. Berikut nomor antrean kunjungan Anda.</p>
        </div>

        {{-- AREA TIKET CETAK --}}
        <div id="print-section" class="mt-8">
            <div class="mx-auto max-w-lg rounded-2xl bg-slate-50 p-6 text-center shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Nomor Antrean Anda</p>
                <p class="mt-2 text-6xl font-bold tracking-tight text-slate-900">{{ $b['queue'] }}</p>
                <p class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                    <x-icon name="lucide:check-circle" class="size-4" /> Tiket siap digunakan
                </p>
            </div>

            <div class="mx-auto mt-6 flex max-w-lg items-center justify-between border-t border-slate-100 pt-6 text-sm">
                <div class="text-left">
                    <p class="font-bold text-slate-900">{{ $b['poli'] ?? 'Poli' }}</p>
                    <p class="text-slate-500">{{ $b['doctor'] ?? 'Dokter' }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-slate-900">{{ $b['date'] }}</p>
                    <p class="text-slate-500">{{ $b['time'] }} WIB</p>
                </div>
            </div>

            <div class="mx-auto mt-6 max-w-lg">
                <div class="flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-brand-700">
                    <x-icon name="lucide:info" class="mt-0.5 shrink-0" />
                    <p class="text-sm">Datang pukul <strong>{{ date('H:i', strtotime($b['time'] . ' -15 minutes')) }} WIB</strong> dan tunjukkan tiket Anda saat daftar ulang di klinik.</p>
                </div>
            </div>
        </div>
        
        {{-- Tombol Aksi --}}
        <div class="mx-auto mt-6 max-w-lg space-y-2">
            <button type="button" onclick="window.print()" class="btn btn-primary w-full justify-center">
                <x-icon name="lucide:printer" class="mr-2" /> Cetak Tiket
            </button>
            <a href="{{ route('portal.home') }}" class="btn btn-outline w-full justify-center">
                Kembali ke Beranda
            </a>
        </div>
    </x-card>
</div>
@endsection