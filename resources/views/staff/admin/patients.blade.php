@extends('layouts.staff')
@section('title', 'Data Pasien')
@section('crumb', 'Data pasien')
@section('heading', 'Data pasien')
@section('subheading', 'Kelola data pasien yang terdaftar di klinik.')
@section('actions')<a href="{{ route('admin.patients.create') }}" class="btn btn-primary"><x-icon name="lucide:user-plus" /> Daftarkan pasien</a>@endsection
@section('content')
<x-card>
    <form class="flex gap-3 p-5"><div class="relative flex-1"><x-icon name="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input name="q" value="{{ $q }}" class="input pl-10" placeholder="Cari nama, NIK, atau no. rekam medis"></div><button class="btn btn-outline">Cari</button></form>
    <div class="overflow-x-auto"><table class="tbl">
        <thead><tr><th>No. RM</th><th>Nama</th><th>NIK</th><th>Umur</th><th>Telepon</th><th>Kunjungan terakhir</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($rows as $p)
            <tr><td class="font-medium">{{ $p['rm'] }}</td><td>{{ $p['name'] }}<p class="text-xs text-slate-400">{{ $p['gender'] }}</p></td><td>{{ $p['nik'] }}</td><td>{{ $p['age'] }} th</td><td>{{ $p['phone'] }}</td><td>{{ $p['last_visit'] }}</td>
                <td class="text-right whitespace-nowrap">
                    <button class="btn btn-outline btn-sm" @click="$dispatch('open-modal', { name: 'patient', data: @js($p) })"><x-icon name="lucide:pencil" class="text-base" /> Ubah</button>
                    <form method="POST" action="{{ route('admin.patients.destroy', $p['id']) }}" class="inline" onsubmit="return confirm('Hapus data pasien ini?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm text-red-600" aria-label="Hapus"><x-icon name="lucide:trash-2" class="text-base" /></button></form></td></tr>
        @empty
            <tr><td colspan="7"><x-empty icon="lucide:user-x" title="Pasien tidak ditemukan" text="Coba kata kunci lain atau daftarkan pasien baru." /></td></tr>
        @endforelse
        </tbody></table></div>
</x-card>

<x-modal name="patient" title="Ubah data pasien">
    <form method="POST" :action="'{{ url('staff/admin/pasien') }}/' + d.id" class="space-y-4">@csrf @method('PUT')
        <x-field label="Nama lengkap" name="name" model="d.name" />
        <x-field label="Nomor telepon" name="phone" model="d.phone" />
        <div><label class="label">Alamat</label><textarea name="address" x-model="d.address" rows="2" class="input"></textarea></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endsection
