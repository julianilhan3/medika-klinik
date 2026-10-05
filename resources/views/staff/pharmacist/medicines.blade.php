@extends('layouts.staff')
@section('title', 'Data Obat')
@section('crumb', 'Data obat')
@section('heading', 'Data Obat')
@section('subheading', 'Daftar obat yang tersedia untuk diresepkan.')
@section('actions')<button class="btn btn-primary" @click="$dispatch('open-modal', { name: 'medicine' })"><x-icon name="lucide:plus" /> Tambah obat</button>@endsection
@section('content')
<x-card>
    <form class="p-5"><div class="relative"><x-icon name="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" /><input name="q" value="{{ $q }}" class="input pl-10" placeholder="Cari nama, kode, atau kategori obat"></div></form>
    <div class="overflow-x-auto"><table class="tbl">
        <thead><tr><th>Kode</th><th>Nama obat</th><th>Kategori</th><th>Satuan</th><th>Harga</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($rows as $m)
            <tr><td class="font-medium">{{ $m['code'] }}</td><td>{{ $m['name'] }}</td><td>{{ $m['category'] }}</td><td>{{ $m['unit'] }}</td><td>Rp {{ number_format($m['price'], 0, ',', '.') }}</td>
                <td class="whitespace-nowrap text-right"><button class="btn btn-outline btn-sm" @click="$dispatch('open-modal', { name: 'medicine', data: @js($m) })"><x-icon name="lucide:pencil" class="text-base" /> Ubah</button>
                    <form method="POST" action="{{ route('pharmacist.medicines.destroy', $m['id']) }}" class="inline" onsubmit="return confirm('Hapus obat ini?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm text-red-600" aria-label="Hapus"><x-icon name="lucide:trash-2" class="text-base" /></button></form></td></tr>
        @empty
            <tr><td colspan="6"><x-empty icon="lucide:pill" title="Obat tidak ditemukan" text="Coba kata kunci lain atau tambahkan obat baru." /></td></tr>
        @endforelse
        </tbody></table></div>
</x-card>

<x-modal name="medicine" title="Data obat">
    <form method="POST" action="{{ route('pharmacist.medicines.store') }}" class="space-y-4">@csrf
        <input type="hidden" name="id" :value="d.id">
        <div class="grid gap-4 sm:grid-cols-2"><x-field label="Kode" name="code" model="d.code" /><x-field label="Satuan" name="unit" model="d.unit" placeholder="Tablet" /></div>
        <x-field label="Nama obat" name="name" model="d.name" />
        <div class="grid gap-4 sm:grid-cols-2"><x-field label="Kategori" name="category" model="d.category" /><x-field label="Harga (Rp)" name="price" type="number" model="d.price" /></div>
        <x-field label="Stok minimum" name="min" type="number" model="d.min" />
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endsection
