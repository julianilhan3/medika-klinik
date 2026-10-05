@extends('layouts.staff')
@section('title', 'Reservasi')
@section('crumb', 'Reservasi')
@section('heading', 'Reservasi')
@section('subheading', 'Kelola booking pasien: setujui, tolak, jadwalkan ulang, atau batalkan.')
@section('content')
<div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-stat label="Total reservasi" :value="$counts['total']" icon="lucide:clipboard-list" />
    <x-stat label="Menunggu persetujuan" :value="$counts['pending']" icon="lucide:clock" tone="amber" />
    <x-stat label="Disetujui" :value="$counts['approved']" icon="lucide:check-circle-2" tone="green" />
    <x-stat label="Dibatalkan" :value="$counts['cancelled']" icon="lucide:x-circle" tone="red" />
</div>

<x-card class="mt-5">
    <form method="GET" class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_2fr_auto]">
        <select name="status" class="input"><option value="">Semua status</option>@foreach (['Menunggu Persetujuan', 'Disetujui', 'Check-in', 'Selesai', 'Dibatalkan'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
        <select name="poli" class="input"><option value="">Semua poli</option>@foreach ($poli as $p)<option @selected(request('poli') === $p['name'])>{{ $p['name'] }}</option>@endforeach</select>
        <input name="q" value="{{ request('q') }}" class="input" placeholder="Cari nama pasien atau kode booking">
        <button class="btn btn-primary">Terapkan</button>
    </form>
    <div class="overflow-x-auto"><table class="tbl min-w-[56rem]">
        <thead><tr><th>Kode</th><th>Pasien</th><th>Poli dan dokter</th><th>Jadwal</th><th>Tipe</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($rows as $b)
            @php $open = in_array($b['status'], ['Menunggu Persetujuan', 'Disetujui']); @endphp
            <tr>
                <td class="font-medium">{{ $b['code'] }}</td>
                <td class="font-medium text-slate-900">{{ $b['patient'] }}<p class="text-xs font-normal text-slate-400">{{ $b['rm'] }}</p><p class="mt-1 max-w-52 text-xs font-normal text-slate-500">{{ $b['complaint'] }}</p></td>
                <td>{{ $b['poli'] }}<p class="text-xs text-slate-400">{{ $b['doctor'] }}</p></td>
                <td class="whitespace-nowrap">{{ $b['date'] }}<p class="text-xs text-slate-400">{{ $b['time'] }} WIB</p></td>
                <td>{{ $b['type'] }}</td><td><x-badge :text="$b['status']" /></td>
                <td class="whitespace-nowrap text-right">
                    @if ($b['status'] === 'Menunggu Persetujuan')
                        <form method="POST" action="{{ route('admin.bookings.update', $b['code']) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="action" value="approve"><button class="btn btn-primary btn-sm">Setujui</button></form>
                        <button class="btn btn-outline btn-sm text-red-600" @click="$dispatch('open-modal', { name: 'booking-reason', data: { code: '{{ $b['code'] }}', action: 'reject', title: 'Tolak reservasi', patient: '{{ $b['patient'] }}' } })">Tolak</button>
                    @endif
                    @if ($open)
                        <button class="btn btn-outline btn-sm" @click="$dispatch('open-modal', { name: 'booking-reschedule', data: { code: '{{ $b['code'] }}', patient: '{{ $b['patient'] }}', date: '', time: '' } })">Jadwal ulang</button>
                        @if ($b['status'] === 'Disetujui')<button class="btn btn-outline btn-sm text-red-600" @click="$dispatch('open-modal', { name: 'booking-reason', data: { code: '{{ $b['code'] }}', action: 'cancel', title: 'Batalkan reservasi', patient: '{{ $b['patient'] }}' } })">Batalkan</button>@endif
                    @else<span class="text-xs text-slate-300">-</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><x-empty icon="lucide:search-x" title="Reservasi tidak ditemukan" text="Ubah filter atau kata kunci pencarian." /></td></tr>
        @endforelse
        </tbody></table></div>
</x-card>

{{-- Alasan tolak / batal --}}
<x-modal name="booking-reason" title="Konfirmasi">
    <form method="POST" :action="'{{ url('staff/admin/reservasi') }}/' + d.code" class="space-y-4">@csrf @method('PATCH')
        <input type="hidden" name="action" :value="d.action">
        <p class="text-sm text-slate-600"><b x-text="d.title"></b> atas nama <b x-text="d.patient"></b>. Pasien akan menerima notifikasi beserta alasannya.</p>
        <div><label class="label">Alasan</label><textarea name="reason" rows="3" required maxlength="255" class="input" placeholder="Contoh: dokter berhalangan hadir"></textarea></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Kembali</button><button class="btn btn-danger">Konfirmasi</button></div>
    </form>
</x-modal>

{{-- Jadwal ulang --}}
<x-modal name="booking-reschedule" title="Jadwalkan ulang reservasi">
    <form method="POST" :action="'{{ url('staff/admin/reservasi') }}/' + d.code" class="space-y-4">@csrf @method('PATCH')
        <input type="hidden" name="action" value="reschedule"><input type="hidden" name="time" :value="d.time">
        <p class="text-sm text-slate-600">Pilih tanggal dan jam baru untuk <b x-text="d.patient"></b>.</p>
        <div><label class="label">Tanggal baru</label><input type="date" name="date" x-model="d.date" min="{{ now()->toDateString() }}" required class="input"></div>
        <div><label class="label">Jam</label><div class="grid grid-cols-3 gap-2">
            @foreach ($slots as $s)<button type="button" @if (! $s['available']) disabled @endif @click="d.time = '{{ $s['time'] }}'" :class="d.time === '{{ $s['time'] }}' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg py-2 text-sm font-medium disabled:bg-slate-50 disabled:text-slate-300 disabled:line-through">{{ $s['time'] }}</button>@endforeach</div></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Batal</button><button class="btn btn-primary" :disabled="!d.date || !d.time">Simpan jadwal baru</button></div>
    </form>
</x-modal>
@endsection
