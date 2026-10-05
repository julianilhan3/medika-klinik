@extends('layouts.staff')
@section('title', 'Persediaan')
@section('crumb', 'Persediaan')
@section('heading', 'Persediaan')
@section('subheading', 'Pantau stok dan tanggal kedaluwarsa obat.')
@section('actions')<button class="btn btn-primary" @click="$dispatch('open-modal', { name: 'stock' })"><x-icon name="lucide:package-plus" /> Stok masuk</button>@endsection
@section('content')
<x-card class="overflow-x-auto">
    <table class="tbl min-w-[44rem]">
        <thead><tr><th>Obat</th><th class="w-56">Stok</th><th>Minimum</th><th>Kedaluwarsa</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($rows as $m)
            @php
                $status = $m['stock'] === 0 ? 'Habis' : ($m['stock'] <= $m['min'] ? 'Menipis' : 'Tersedia');
                $pct = min(100, round($m['stock'] / max($m['min'] * 2, 1) * 100));
                $bar = ['Habis' => 'bg-red-500', 'Menipis' => 'bg-amber-500', 'Tersedia' => 'bg-emerald-500'][$status];
                $soon = \Illuminate\Support\Carbon::parse($m['expiry'])->lt(now()->addMonths(3));
            @endphp
            <tr><td class="font-medium text-slate-900">{{ $m['name'] }}<p class="text-xs font-normal text-slate-400">{{ $m['code'] }}</p></td>
                <td><div class="flex items-center gap-3"><div class="h-2 flex-1 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div></div><span class="w-16 text-right text-sm font-semibold">{{ $m['stock'] }} {{ $m['unit'] === 'Tablet' ? 'tab' : 'kps' }}</span></div></td>
                <td>{{ $m['min'] }}</td>
                <td class="{{ $soon ? 'font-medium text-red-600' : '' }}">{{ \Illuminate\Support\Carbon::parse($m['expiry'])->translatedFormat('j M Y') }}</td>
                <td><x-badge :text="$status" /></td></tr>
        @endforeach
        </tbody>
    </table>
</x-card>

<x-modal name="stock" title="Catat stok masuk">
    <form method="POST" action="{{ route('pharmacist.stock.store') }}" class="space-y-4">@csrf
        <div><label class="label">Obat</label><select name="medicine" class="input">@foreach ($rows as $m)<option value="{{ $m['id'] }}">{{ $m['name'] }}</option>@endforeach</select></div>
        <div class="grid gap-4 sm:grid-cols-2"><x-field label="Jumlah masuk" name="qty" type="number" min="1" /><x-field label="Kedaluwarsa" name="expiry" type="date" /></div>
        <x-field label="Nomor batch (opsional)" name="batch" />
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-outline" @click="open = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endsection
