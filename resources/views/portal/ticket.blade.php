@extends('layouts.portal')
@section('title', 'Detail Tiket')
@section('content')

{{-- CSS KHUSUS CETAK --}}
<style>
    @media print {
        @page { margin: 0; }
        
        html, body {
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        body * {
            visibility: hidden;
        }
        
        #print-section, #print-section * {
            visibility: visible;
        }
        
        #print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 2cm;
            margin: 0;
        }
    }
</style>

<div class="mx-auto max-w-3xl space-y-5">
    <x-card class="space-y-5 p-5 sm:p-8 print:border-none print:p-0 print:shadow-none">
        
        {{-- Area ini akan disembunyikan saat cetak --}}
        <div class="print:hidden">
            @include('partials.portal-stepper', ['current' => 3, 'steps' => ['Pilih jadwal', 'Konfirmasi booking', 'Tiket & antrean']])
            
            <div class="mt-5">
                <a href="{{ route('tickets.index') }}" class="flex w-fit items-center gap-1 text-xs text-brand-600 hover:underline"><x-icon name="lucide:arrow-left" /> Kembali ke Tiket & Antrean</a>
                <h1 class="mt-2 text-2xl font-bold">Detail Tiket & Antrean</h1>
                <p class="text-sm text-slate-600">Simpan nomor ini dan tunjukkan kepada petugas saat check-in.</p>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- 🖨️ AREA KHUSUS CETAK DIMULAI DI SINI 🖨 --}}
        {{-- ========================================== --}}
        <div id="print-section" class="grid gap-5 sm:grid-cols-[1fr_auto] print:grid-cols-[1fr_auto] print:items-stretch">
            <div class="rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between"><h2 class="font-semibold">Detail booking</h2><x-badge :text="$t['status']" /></div>
                <p class="text-xs text-slate-500">Kode Booking: <b class="text-slate-800">{{ $t['code'] }}</b></p>
                <dl class="mt-3 divide-y divide-slate-100 text-sm">
                   @foreach (['Dokter' => $t['doctor'], 'Poli' => $t['poli'], 'Tanggal' => $t['date'], 'Jam' => $t['time'].' WIB', 'Nama Pasien' => $t['patient']] as $k => $v)
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
                    @endforeach
                </dl>
            </div>
            <div class="grid place-items-center rounded-2xl bg-brand-50 px-8 py-6 text-center print:border print:border-slate-200 print:bg-transparent">
                <div>
                    <p class="text-xs text-slate-500">Nomor antrean Anda</p>
                    <p class="my-2 text-5xl font-bold text-brand-600">{{ $t['queue'] }}</p>
                    <p class="text-xs text-slate-500">Poli {{ $t['poli'] }}</p>
                </div>
            </div>
        </div>
        {{-- ========================================== --}}
        {{-- 🖨 AREA KHUSUS CETAK BERAKHIR DI SINI 🖨️ --}}
        {{-- ========================================== --}}

        {{-- Area di bawah ini juga disembunyikan saat cetak --}}
        <div class="print:hidden space-y-5">
            @if (isset($live) && $live)<div>@include('partials.queue-live')</div>@endif

            @if ($t['status'] === 'Menunggu Persetujuan')
                <p class="flex items-center gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-800"><x-icon name="lucide:info" /> Dokter akan meninjau booking Anda. Pembaruan status tampil di halaman ini.</p>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                <button type="button" onclick="window.print()" class="btn btn-primary"><x-icon name="lucide:download" /> Unduh Tiket (PDF)</button>
                <a href="{{ route('tickets.index') }}" class="btn btn-outline"><x-icon name="lucide:bookmark" /> Simpan Booking</a>
            </div>
            <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:info" /> Tiket tetap bisa dibuka lagi dari menu "Tiket Antrean" kapan saja.</p>
        </div>

    </x-card>
</div>
@endsection