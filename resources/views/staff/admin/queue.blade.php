@extends('layouts.staff')
@section('title', 'Antrean')
@section('crumb', 'Monitoring antrean')
@section('heading', 'Dashboard Antrean')
@section('subheading', 'Pantau antrean dan status pelayanan pasien hari ini.')
@section('actions')<span class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><x-icon name="lucide:calendar" /> {{ now()->translatedFormat('l, j F Y') }}</span>@endsection
@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Total Booking" :value="$stats['total']" hint="Seluruh poli hari ini" icon="lucide:ticket" />
    <x-stat label="Menunggu" :value="$stats['waiting']" hint="Menanti pemeriksaan" icon="lucide:clock" tone="amber" />
    <x-stat label="Sedang Diperiksa" :value="$stats['serving']" hint="Dalam pelayanan dokter" icon="lucide:stethoscope" tone="blue" />
    <x-stat label="Selesai" :value="$stats['done']" hint="Pemeriksaan selesai" icon="lucide:check-circle-2" tone="green" />
</div>

<div class="mt-5 flex items-center gap-3 rounded-xl border border-brand-100 bg-brand-50 p-4 text-sm text-brand-700">
    <x-icon name="lucide:info" class="text-lg" /> Antrean diperbarui otomatis setiap beberapa detik. Gunakan tombol perbarui jika data tampak tertinggal.
</div>

<x-card class="mt-5">
    <div class="flex flex-wrap items-start justify-between gap-3 p-5 pb-3">
        <div><h2 class="font-semibold">Antrean pasien</h2><p class="text-sm text-slate-500">Daftar kunjungan {{ now()->translatedFormat('j F Y') }}</p></div>
        <a href="{{ request()->fullUrl() }}" class="btn btn-outline btn-sm"><x-icon name="lucide:refresh-cw" /> Perbarui antrean</a>
    </div>
    <form method="GET" class="grid gap-3 px-5 pb-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_2fr_auto]">
        <select name="poli" class="input"><option value="">Semua poli</option>@foreach ($poli as $p)<option @selected(request('poli') === $p['name'])>{{ $p['name'] }}</option>@endforeach</select>
        <select name="status" class="input"><option value="">Semua status</option>@foreach (['Menunggu', 'Sedang Diperiksa', 'Selesai', 'Batal'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
        <input name="q" value="{{ request('q') }}" class="input" placeholder="Cari nama atau nomor antrean">
        <button class="btn btn-primary">Terapkan</button>
    </form>
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>No Antrean</th><th>Nama Pasien</th><th>Dokter</th><th>Jam</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr><td class="font-semibold">{{ $r['no'] }}</td><td>{{ $r['patient'] }}</td><td>{{ $r['doctor'] }}<p class="text-xs text-slate-400">{{ $r['poli'] }}</p></td><td>{{ $r['time'] }}</td><td><x-badge :text="$r['status']" /></td></tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="lucide:search-x" title="Tidak ada antrean yang cocok" text="Ubah filter atau kata kunci pencarian." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Menampilkan {{ count($rows) }} dari {{ $stats['total'] }} booking</p>
</x-card>
@endsection
