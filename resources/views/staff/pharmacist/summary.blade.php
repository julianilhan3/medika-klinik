@extends('layouts.staff')
@section('title', 'Ringkasan')
@section('crumb', 'Ringkasan')
@section('heading', 'Ringkasan')
@section('subheading', 'Pantau resep dari dokter, penyerahan obat, dan persediaan.')
@section('content')
<div class="grid grid-cols-2 gap-4 xl:grid-cols-5">
    <x-stat label="Resep menunggu" :value="$counts['Menunggu']" hint="Belum diproses" icon="lucide:inbox" tone="amber" />
    <x-stat label="Sedang diproses" :value="$counts['Diproses']" hint="Dalam peracikan" icon="lucide:flask-conical" />
    <x-stat label="Siap diserahkan" :value="$counts['Siap']" hint="Menunggu pasien" icon="lucide:package-check" tone="green" />
    <x-stat label="Sudah diserahkan" :value="$counts['Diserahkan']" hint="Hari ini" icon="lucide:hand-helping" tone="blue" />
    <x-stat label="Stok menipis" :value="count($low)" hint="Di bawah minimum" icon="lucide:triangle-alert" tone="red" />
</div>
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <x-card>
        <div class="flex items-center justify-between p-5 pb-3"><h2 class="font-semibold">Resep terbaru</h2><a href="{{ route('pharmacist.prescriptions') }}" class="text-sm font-medium text-brand-600 hover:underline">Lihat semua</a></div>
        <ul class="divide-y divide-slate-100">@foreach ($recent as $r)<li><a href="{{ route('pharmacist.prescriptions.show', $r['code']) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50"><div><p class="text-sm font-medium">{{ $r['code'] }} - {{ $r['patient'] }}</p><p class="text-xs text-slate-500">{{ $r['doctor'] }} - {{ $r['date'] }}</p></div><x-badge :text="$r['status']" /></a></li>@endforeach</ul>
    </x-card>
    <x-card>
        <div class="flex items-center justify-between p-5 pb-3"><h2 class="font-semibold">Perlu restok</h2><a href="{{ route('pharmacist.stock') }}" class="text-sm font-medium text-brand-600 hover:underline">Kelola stok</a></div>
        <ul class="divide-y divide-slate-100">@foreach ($low as $m)<li class="flex items-center justify-between gap-3 px-5 py-3"><div><p class="text-sm font-medium">{{ $m['name'] }}</p><p class="text-xs text-slate-500">Minimum {{ $m['min'] }} {{ strtolower($m['unit']) }}</p></div><div class="flex items-center gap-3"><x-badge :text="$m['stock'] === 0 ? 'Habis' : 'Menipis'" /><span class="w-10 text-right text-sm font-semibold">{{ $m['stock'] }}</span></div></li>@endforeach</ul>
    </x-card>
</div>
@endsection
