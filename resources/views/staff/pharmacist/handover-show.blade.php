@extends('layouts.staff')
@section('title', 'Penyerahan Obat')
@section('crumb', 'Penyerahan obat / '.$rx['code'])
@section('heading', 'Penyerahan Obat')
@section('subheading', 'Periksa resep, cetak etiket, lalu konfirmasi obat diterima pasien.')
@section('actions')
    <a href="{{ route('pharmacist.handover') }}" class="btn btn-outline"><x-icon name="lucide:arrow-left" /> Kembali</a>
@endsection
@section('content')
@php $done = $rx['status'] === 'Diserahkan'; @endphp

<!-- Progress Bar -->
<div class="grid gap-3 sm:grid-cols-3">
    @foreach ([['Resep siap', 'lucide:check'], ['Cetak etiket', 'lucide:printer'], ['Penyerahan obat', 'lucide:hand-helping']] as $i => [$t, $ic])
        <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70">
            <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $i === 0 || $done ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-50 text-brand-600' }}">
                <x-icon :name="$ic" class="text-base" />
            </span>
            <p class="text-sm font-semibold">{{ $t }}</p>
        </div>
    @endforeach
</div>

<!-- Info Pasien -->
<x-card class="mt-5 p-5 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start sm:items-center gap-3 min-w-0">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-600">
                <x-icon name="lucide:user-round" class="text-2xl" />
            </span>
            <div class="min-w-0">
                <p class="truncate text-lg font-bold">{{ $rx['patient'] }}</p>
                <p class="truncate text-sm text-slate-500">{{ $rx['gender'] }} - {{ $rx['age'] }} tahun - {{ $rx['dob'] }}</p>
            </div>
        </div>
        <div class="shrink-0 text-left sm:text-right">
            <p class="mb-1 text-xs text-slate-500">Status resep</p>
            <x-badge :text="$done ? 'Diserahkan' : 'Siap diambil'" tone="green" />
        </div>
    </div>
    
    <dl class="mt-5 grid grid-cols-1 gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
        @foreach (['No. rekam medis' => $rx['rm'], 'No. booking' => $rx['booking'], 'No. resep' => $rx['code'], 'Dokter pemeriksa' => $rx['doctor']] as $k => $v)
            <div>
                <dt class="text-xs text-slate-500">{{ $k }}</dt>
                <dd class="font-semibold">{{ $v }}</dd>
            </div>
        @endforeach
    </dl>
    
    @if ($rx['allergy'])
        <p class="mt-4 flex items-start sm:items-center gap-2 rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">
            <x-icon name="lucide:triangle-alert" class="shrink-0" /> Alergi tercatat: {{ $rx['allergy'] }}
        </p>
    @endif
</x-card>

<!-- Grid Utama (Tabel & Form Konfirmasi) -->
<!-- PERBAIKAN: Menambahkan min-w-0 di parent grid -->
<div class="mt-5 grid min-w-0 gap-5 lg:grid-cols-[1fr_22rem]">
    
    <!-- Kolom Kiri: Tabel Resep -->
    <!-- PERBAIKAN: Menambahkan min-w-0 pada card agar tidak melar melebihi layar -->
    <x-card class="min-w-0 w-full">
        <div class="flex items-center justify-between p-5 pb-3">
            <h2 class="font-semibold">Detail resep</h2>
            <span class="text-xs text-slate-500">{{ count($rx['items']) }} jenis obat</span>
        </div>
        
        <!-- Pembungkus tabel yang bisa di-scroll -->
        <div class="w-full overflow-x-auto">
            <table class="tbl w-full min-w-[32rem] whitespace-nowrap">
                <thead>
                    <tr>
                        <th>Nama obat</th>
                        <th>Jumlah</th>
                        <th>Aturan pakai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rx['items'] as $i)
                        <tr>
                            <td class="font-medium text-slate-900">
                                {{ $i['name'] }}
                                <p class="text-xs font-normal text-slate-400">{{ $i['form'] }}</p>
                            </td>
                            <td>{{ $i['qty'] }} {{ $i['unit'] }}</td>
                            <td>
                                {{ $i['dose'] }}
                                <p class="text-xs text-slate-400">{{ $i['rule'] }}</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="flex items-start gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
            <x-icon name="lucide:circle-check" class="mt-0.5 shrink-0 text-base" /> 
            Sampaikan aturan pakai dan pastikan pasien memahami cara penggunaan setiap obat.
        </p>
    </x-card>

    <!-- Kolom Kanan: Aksi Penyerahan -->
    <x-card class="h-fit p-5" x-data="{ verified: false }">
        <h2 class="font-semibold">Penyerahan ke pasien</h2>
        
        <div class="mt-4 rounded-xl bg-brand-50 p-4 text-center sm:text-left">
            <p class="text-xs text-slate-500">Nomor antrean farmasi</p>
            <p class="text-3xl font-bold text-brand-600">{{ $rx['pharmacy_no'] }}</p>
            <p class="text-sm font-medium">{{ $rx['patient'] }}</p>
        </div>
        
        @if ($done)
            <p class="mt-5 rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm text-slate-600">
                Obat sudah diserahkan pada <span class="font-medium">{{ $rx['handed_at'] }}</span> oleh <span class="font-medium">{{ $rx['handed_by'] }}</span>.
            </p>
        @else
            <div class="mt-5 space-y-3">
                <form method="POST" action="{{ route('pharmacist.handover.call', $rx['code']) }}">
                    @csrf
                    <button class="btn btn-outline w-full justify-center">
                        <x-icon name="lucide:volume-2" /> Panggil Pasien
                    </button>
                </form>
                <button type="button" onclick="window.print()" class="btn btn-outline w-full justify-center">
                    <x-icon name="lucide:printer" /> Cetak Etiket
                </button>
            </div>
            
            <form method="POST" action="{{ route('pharmacist.handover.confirm', $rx['code']) }}" class="mt-5 border-t border-slate-100 pt-5">
                @csrf
                <label class="flex items-start gap-3 cursor-pointer text-xs text-slate-600">
                    <input type="checkbox" name="verified" value="1" x-model="verified" class="mt-0.5 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> 
                    <span class="leading-relaxed">Nama dan tanggal lahir pasien sudah dicocokkan.</span>
                </label>
                @error('verified')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                
                <button class="btn btn-primary mt-4 w-full justify-center" :disabled="!verified">
                    Konfirmasi Obat Diserahkan
                </button>
                <p class="mt-2 text-center text-xs text-slate-500">Konfirmasi hanya setelah obat diterima pasien.</p>
            </form>
        @endif
    </x-card>
</div>
@endsection