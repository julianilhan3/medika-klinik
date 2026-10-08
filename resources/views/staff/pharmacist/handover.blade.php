@extends('layouts.staff')
@section('title', 'Penyerahan Obat')
@section('crumb', 'Penyerahan obat')
@section('heading', 'Penyerahan Obat')
@section('subheading', 'Resep yang sudah siap. Panggil pasien, cetak etiket, lalu serahkan obat.')
@section('content')
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse ($rows as $r)
    <x-card class="p-5">
        <div class="flex items-start justify-between gap-3"><div><p class="text-xs text-slate-500">Nomor antrean farmasi</p><p class="text-3xl font-bold text-brand-600">{{ $r['pharmacy_no'] }}</p></div><x-badge text="Siap diambil" /></div>
        <p class="mt-3 font-semibold">{{ $r['patient'] }}</p><p class="text-xs text-slate-500">{{ $r['code'] }} - {{ count($r['items']) }} jenis obat - {{ $r['doctor'] }}</p>
        <a href="{{ route('pharmacist.handover.show', $r['code']) }}" class="btn btn-primary mt-4 w-full">Mulai penyerahan</a>
    </x-card>
@empty
    <x-card class="md:col-span-2 xl:col-span-3"><x-empty icon="lucide:package-check" title="Belum ada obat siap diserahkan" text="Resep yang ditandai siap akan muncul di sini." /></x-card>
@endforelse
</div>
@endsection
