@extends('layouts.staff')
@section('title', 'Riwayat Penyerahan')
@section('crumb', 'Riwayat penyerahan')
@section('heading', 'Riwayat Penyerahan')
@section('subheading', 'Catatan obat yang sudah diserahkan kepada pasien.')
@section('content')
<x-card class="overflow-x-auto">
    <table class="tbl min-w-[40rem]">
        <thead><tr><th>Resep</th><th>Pasien</th><th>Obat</th><th>Waktu penyerahan</th><th>Petugas</th></tr></thead>
        <tbody>
        @forelse ($rows as $r)
            <tr><td class="font-medium">{{ $r['code'] }}<p class="text-xs font-normal text-slate-400">{{ $r['pharmacy_no'] }}</p></td><td class="font-medium text-slate-900">{{ $r['patient'] }}<p class="text-xs font-normal text-slate-400">{{ $r['rm'] }}</p></td>
                <td>@foreach ($r['items'] as $i)<p>{{ $i['name'] }} <span class="text-slate-400">x{{ $i['qty'] }}</span></p>@endforeach</td><td>{{ $r['handed_at'] }}</td><td>{{ $r['handed_by'] }}</td></tr>
        @empty
            <tr><td colspan="5"><x-empty icon="lucide:history" title="Belum ada penyerahan" text="Riwayat muncul setelah obat diserahkan kepada pasien." /></td></tr>
        @endforelse
        </tbody>
    </table>
</x-card>
@endsection
