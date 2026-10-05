@extends('layouts.staff')
@section('title', 'Jadwal Praktik')
@section('crumb', 'Jadwal praktik')
@section('heading', 'Jadwal Praktik')
@section('subheading', 'Kelola jam praktik dan cuti dokter dalam satu kalender.')
@section('actions')
<div class="flex gap-2">
    <button class="btn btn-outline" @click="$dispatch('open-modal', { name: 'leave' })"><x-icon name="lucide:calendar-off" /> Blokir Cuti</button>
    <button class="btn btn-primary" @click="$dispatch('open-modal', { name: 'session' })"><x-icon name="lucide:plus" /> Atur Jadwal</button>
</div>
@endsection
@section('content')
<x-card class="p-5">
    <form method="GET" class="grid gap-4 sm:grid-cols-[minmax(0,22rem)_1fr_auto] sm:items-end">
        <div><label class="label">Pilih dokter</label>
            <select name="doctor" class="input" onchange="this.form.submit()">@foreach ($doctors as $d)<option value="{{ $d['id'] }}" @selected($d['id'] === $doctor['id'])>{{ $d['name'] }}</option>@endforeach</select></div>
        <div class="text-sm"><p class="text-xs text-slate-500">Spesialis</p><p class="font-medium">{{ $doctor['poli'] }} - Cabang Utama</p></div>
        <x-badge :text="$doctor['status']" />
    </form>
</x-card>

<div class="mt-5 grid gap-4 sm:grid-cols-3">
    <x-stat label="Jadwal minggu ini" :value="$stats['sessions'].' sesi praktik'" icon="lucide:calendar-days" />
    <x-stat label="Total kuota" :value="$stats['quota'].' pasien'" icon="lucide:users" tone="blue" />
    <x-stat label="Reservasi terdaftar" :value="$stats['booked'].' pasien'" icon="lucide:clipboard-check" tone="green" />
</div>

<x-card class="mt-5">
    <div class="flex flex-wrap items-center justify-between gap-3 p-5">
        <h2 class="flex items-center gap-2 font-semibold"><x-icon name="lucide:calendar" class="text-brand-600" /> {{ $weekStart->translatedFormat('j') }}-{{ $weekStart->copy()->addDays(6)->translatedFormat('j F Y') }}</h2>
        <div class="flex gap-2">
            <a href="{{ route('admin.schedule', ['doctor' => $doctor['id']]) }}" class="btn btn-outline btn-sm">Hari ini</a>
            <a href="{{ route('admin.schedule', ['doctor' => $doctor['id'], 'week' => $week - 1]) }}" class="btn btn-outline btn-sm" aria-label="Minggu sebelumnya"><x-icon name="lucide:chevron-left" /></a>
            <a href="{{ route('admin.schedule', ['doctor' => $doctor['id'], 'week' => $week + 1]) }}" class="btn btn-outline btn-sm" aria-label="Minggu berikutnya"><x-icon name="lucide:chevron-right" /></a>
        </div>
    </div>
    @include('partials.week-calendar')
</x-card>
<p class="mt-4 flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:info" class="text-base" /> Jadwal berulang setiap minggu. Tanggal cuti menutup seluruh slot pada hari tersebut.</p>

{{-- Atur jadwal --}}
<x-modal name="session" title="Atur jadwal praktik">
    <form method="POST" action="{{ route('admin.schedule.store') }}" class="space-y-4">@csrf
        <input type="hidden" name="doctor" value="{{ $doctor['id'] }}">
        <p class="text-sm text-slate-500">Sesi berulang setiap minggu untuk {{ $doctor['name'] }}.</p>
        <div><label class="label">Hari</label><select name="day" class="input">@foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $i => $n)<option value="{{ $i }}">{{ $n }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-3"><x-field label="Mulai" name="start" type="time" value="08:00" /><x-field label="Selesai" name="end" type="time" value="11:00" /></div>
        <x-field label="Kuota pasien" name="quota" type="number" value="15" min="1" />
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>

{{-- Blokir cuti --}}
<x-modal name="leave" title="Blokir cuti dokter">
    <form method="POST" action="{{ route('admin.schedule.leave') }}" class="space-y-4">@csrf
        <input type="hidden" name="doctor" value="{{ $doctor['id'] }}">
        <p class="text-sm text-slate-500">{{ $doctor['name'] }}. Jika ada reservasi pada tanggal itu, Anda diminta meninjaunya dulu.</p>
        <x-field label="Tanggal cuti" name="date" type="date" min="{{ now()->toDateString() }}" value="{{ now()->next('Friday')->toDateString() }}" />
        <div><label class="label">Cakupan</label><select name="scope" class="input"><option value="full">Cuti sehari penuh</option><option value="session">Satu sesi saja</option></select></div>
        <x-field label="Alasan" name="reason" placeholder="Contoh: keperluan keluarga" />
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Batal</button><button class="btn btn-primary">Lanjutkan</button></div>
    </form>
</x-modal>

{{-- Ada reservasi pada tanggal cuti --}}
@if ($conflict)
<div x-data="{ open: true, agree: false }" x-show="open" x-on:keydown.escape.window="open = false" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto p-4">
    <div class="fixed inset-0 bg-slate-900/50" @click="open = false"></div>
    <div class="relative w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4"><div><h3 class="text-xl font-bold text-slate-900">Ada reservasi pada tanggal cuti</h3><p class="mt-1 text-sm text-slate-500">Tinjau reservasi sebelum melanjutkan blokir cuti.</p></div>
            <button @click="open = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100" aria-label="Tutup"><x-icon name="lucide:x" class="text-xl" /></button></div>
        <p class="mt-4 flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm font-semibold"><x-icon name="lucide:stethoscope" class="text-brand-600" /> {{ $conflict['doctor'] }}</p>
        <div class="mt-4 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><x-icon name="lucide:triangle-alert" class="mt-0.5 text-xl" />
            <div><p class="font-semibold">{{ count($conflict['rows']) }} pasien sudah memiliki reservasi</p><p>{{ $conflict['date'] }}. Blokir cuti akan membatalkan reservasi ini.</p></div></div>
        <p class="mt-4 text-sm"><b>Alasan:</b> {{ $conflict['reason'] }}</p>
        <p class="text-sm text-slate-500">{{ $conflict['poli'] }} - {{ $conflict['scope'] === 'full' ? 'Cuti sehari penuh' : 'Satu sesi' }}</p>
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200"><table class="tbl"><thead><tr><th>Pasien</th><th>Jam</th><th>Status</th></tr></thead>
            <tbody>@foreach ($conflict['rows'] as $c)<tr><td class="font-medium">{{ $c['patient'] }}</td><td>{{ $c['time'] }}</td><td><x-badge :text="$c['status']" /></td></tr>@endforeach</tbody></table></div>
        <form method="POST" action="{{ route('admin.schedule.leave') }}" class="mt-4">@csrf
            @foreach ($conflict['raw'] as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="hidden" name="confirm" value="1">
            <label class="flex items-start gap-2 text-sm text-slate-700"><input type="checkbox" x-model="agree" class="mt-0.5 rounded border-slate-300 text-brand-600"> Saya setuju membatalkan {{ count($conflict['rows']) }} reservasi dan mengirim notifikasi kepada pasien.</label>
            <button class="btn btn-danger mt-4 w-full" :disabled="!agree">Blokir Cuti &amp; Batalkan Reservasi</button>
            <button type="button" @click="open = false" class="btn btn-outline mt-2 w-full">Kembali</button>
        </form>
    </div>
</div>
@endif
@endsection
