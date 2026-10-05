@extends('layouts.staff')
@section('title', 'Resep Obat')
@section('crumb', 'Resep obat')
@section('heading', 'Resep Obat')
@section('subheading', 'Resep masuk dari dokter. Periksa stok, proses, lalu siapkan untuk diserahkan.')
@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    @foreach ([null => 'Semua', 'Menunggu' => 'Menunggu', 'Diproses' => 'Diproses', 'Siap' => 'Siap', 'Diserahkan' => 'Diserahkan'] as $k => $l)
        <a href="{{ route('pharmacist.prescriptions', $k ? ['status' => $k] : []) }}" class="rounded-full px-4 py-1.5 text-sm font-medium {{ ($status ?? null) == $k ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">{{ $l }}</a>
    @endforeach
</div>
<x-card class="overflow-x-auto">
    <table class="tbl min-w-[40rem]">
        <thead><tr><th>Resep</th><th>Pasien</th><th>Dokter</th><th>Obat</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($rows as $r)
            <tr><td class="font-medium">{{ $r['code'] }}<p class="text-xs font-normal text-slate-400">{{ $r['date'] }}</p></td>
                <td class="font-medium text-slate-900">{{ $r['patient'] }}@if ($r['allergy'])<p class="flex items-center gap-1 text-xs font-medium text-red-600"><x-icon name="lucide:triangle-alert" class="text-sm" /> Alergi {{ $r['allergy'] }}</p>@endif</td>
                <td>{{ $r['doctor'] }}</td><td>{{ count($r['items']) }} jenis</td><td><x-badge :text="$r['status']" /></td>
                <td class="text-right"><a href="{{ route('pharmacist.prescriptions.show', $r['code']) }}" class="btn btn-outline btn-sm">Buka detail</a></td></tr>
        @empty
            <tr><td colspan="6"><x-empty icon="lucide:file-check" title="Tidak ada resep" text="Resep dari dokter akan muncul di sini." /></td></tr>
        @endforelse
        </tbody>
    </table>
</x-card>
@endsection
