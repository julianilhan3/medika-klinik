@extends('layouts.staff')
@section('title', 'Kelola Staf')
@section('crumb', 'Kelola staf')
@section('heading', 'Kelola staf')
@section('subheading', 'Atur akun dokter dan apoteker. Peran menentukan menu yang mereka lihat saat masuk.')
@section('actions')<button class="btn btn-primary" @click="$dispatch('open-modal', { name: 'staff', data: { id: null, role: '{{ $tab }}', status: 'Aktif', poli: '{{ $poli[0]['name'] ?? '' }}' } })">
    <x-icon name="lucide:user-plus" /> Tambah {{ $tab === 'doctor' ? 'dokter' : 'apoteker' }}
</button>@endsection
@section('content')

{{-- Daftar kesalahan + buka lagi modal dengan isian sebelumnya --}}
@if ($errors->any())
<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
    <p class="font-semibold">Periksa isian berikut:</p>
    <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
<div x-data x-init="setTimeout(() => $dispatch('open-modal', { name: 'staff', data: @js(collect(old())->except('_token', '_method', 'password', 'photo')->all() + ['id' => old('_id') ?: null, 'role' => old('role', $tab)]) }), 100)"></div>
@endif

<div class="mb-4 inline-flex rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-200/70">
    @foreach (['doctor' => 'Dokter', 'pharmacist' => 'Apoteker'] as $k => $l)
        <a href="{{ route('admin.staff.index', ['tab' => $k]) }}" class="rounded-lg px-5 py-2 text-sm font-medium {{ $tab === $k ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ $l }}</a>
    @endforeach
</div>
<x-card class="overflow-x-auto">
    <table class="tbl">
        <thead><tr><th>Nama</th><th>{{ $tab === 'doctor' ? 'Poli / SIP' : 'SIPA' }}</th><th>Username</th><th>Telepon</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @foreach ($rows as $s)
            <tr>
                <td><div class="flex items-center gap-3">@if ($tab === 'doctor')<x-doctor-avatar :name="$s['name']" size="size-10" />@endif<span class="font-medium text-slate-900">{{ $s['name'] }}</span></div></td>
                <td>@if ($tab === 'doctor'){{ $s['poli'] }}<p class="text-xs text-slate-400">{{ $s['sip'] }}</p>@else{{ $s['sip'] }}@endif</td>
                <td>{{ $s['username'] }}</td><td>{{ $s['phone'] }}</td><td><x-badge :text="$s['status']" /></td>
                <td class="whitespace-nowrap text-right">
                    <button class="btn btn-outline btn-sm" @click="$dispatch('open-modal', { name: 'staff', data: @js($s + ['role' => $tab, 'photo_url' => $tab === 'doctor' ? \App\Support\DoctorPhoto::url($s['name']) : null]) })"><x-icon name="lucide:pencil" class="text-base" /> Ubah</button>
                    <form method="POST" action="{{ route('admin.staff.destroy', $s['id']) }}" class="inline" onsubmit="return confirm('Nonaktifkan akun ini?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm text-red-600" aria-label="Nonaktifkan"><x-icon name="lucide:user-x" class="text-base" /></button></form></td></tr>
        @endforeach
        </tbody>
    </table>
</x-card>

<x-modal name="staff" title="Data staf">
    <form method="POST" enctype="multipart/form-data" :action="d.id ? '{{ url('staff/admin/staf') }}/' + d.id : '{{ route('admin.staff.store') }}'" class="space-y-4">@csrf
        <input type="hidden" name="_method" :value="d.id ? 'PUT' : 'POST'"><input type="hidden" name="role" :value="d.role">
        <input type="hidden" name="_id" :value="d.id">

        {{-- Unggah foto profil (khusus dokter) --}}
        @if ($tab === 'doctor')
        <div x-data="{ prev: null }" x-effect="d; prev = null" class="flex items-center gap-4">
            <template x-if="prev || d.photo_url"><img :src="prev || d.photo_url" alt="" class="size-16 rounded-xl object-cover"></template>
            <template x-if="!(prev || d.photo_url)"><span class="grid size-16 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="lucide:user-round" class="text-2xl" /></span></template>
            <div class="min-w-0 flex-1">
                <label class="label">Foto profil</label>
                <input type="file" name="photo" accept="image/png,image/jpeg,image/webp"
                       @change="prev = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                       class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-600 hover:file:bg-brand-100">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP, maksimal 2 MB. Foto tampil otomatis di beranda dan jadwal dokter pasien.</p>
                <label class="mt-1 flex items-center gap-2 text-xs text-slate-600" x-show="d.photo_url"><input type="checkbox" name="remove_photo" value="1" class="rounded"> Hapus foto saat ini</label>
            </div>
        </div>
        @endif

        <x-field label="Nama lengkap" name="name" model="d.name" />
        <div class="grid gap-4 sm:grid-cols-2"><x-field label="Username" name="username" model="d.username" /><x-field label="Telepon" name="phone" model="d.phone" /></div>
        <div class="grid gap-4 sm:grid-cols-2"><x-field label="Nomor SIP/SIPA" name="sip" model="d.sip" />
            <div><label class="label">Status</label><select name="status" x-model="d.status" class="input"><option>Aktif</option><option>Nonaktif</option></select></div></div>
        @if ($tab === 'doctor')<div><label class="label">Poli</label><select name="poli" x-model="d.poli" class="input">@foreach ($poli as $p)<option>{{ $p['name'] }}</option>@endforeach</select></div>@endif
        <x-field label="Password" name="password" type="password" hint="Kosongkan saat mengubah jika tidak ingin mengganti password." />
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endsection