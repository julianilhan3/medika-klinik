@extends('layouts.staff')
@section('title', 'Detail Resep')
@section('crumb', 'Resep obat / Detail')
@section('heading', 'Detail Resep')
@section('subheading', $rx['code'].' - '.$rx['date'])
@section('actions')<div class="flex items-center gap-3"><x-badge :text="$rx['status']" /><a href="{{ route('pharmacist.prescriptions') }}" class="btn btn-outline"><x-icon name="lucide:arrow-left" /> Daftar resep</a></div>@endsection
@section('content')
@php $problem = collect($rx['items'])->contains(fn ($i) => $i['availability'] !== 'Tersedia'); @endphp
<x-card class="p-5">
    <dl class="grid gap-4 text-sm sm:grid-cols-3">
        <div><dt class="text-xs text-slate-500">Pasien</dt><dd class="font-semibold">{{ $rx['patient'] }}</dd><dd class="text-xs text-slate-500">{{ $rx['gender'] }} - {{ $rx['age'] }} tahun - {{ $rx['rm'] }}</dd></div>
        <div><dt class="text-xs text-slate-500">Dokter penulis resep</dt><dd class="font-semibold">{{ $rx['doctor'] }}</dd><dd class="text-xs text-slate-500">{{ $rx['poli'] }}</dd></div>
        <div><dt class="text-xs text-slate-500">Riwayat alergi</dt><dd class="font-semibold {{ $rx['allergy'] ? 'text-red-600' : '' }}">{{ $rx['allergy'] ?: 'Tidak ada alergi tercatat' }}</dd><dd class="text-xs text-slate-500">Kunjungan rawat jalan</dd></div>
    </dl>
</x-card>

<div class="mt-5 grid gap-5 xl:grid-cols-[1fr_20rem]">
    <div class="space-y-5">
        <x-card>
            <div class="p-5 pb-3"><h2 class="font-semibold">Obat dalam resep</h2><p class="text-sm text-slate-500">{{ count($rx['items']) }} obat - Periksa stok sebelum penyerahan</p></div>
            <div class="overflow-x-auto"><table class="tbl min-w-[36rem]">
                <thead><tr><th>Nama obat dan aturan pakai</th><th>Jumlah</th><th>Stok</th></tr></thead>
                <tbody>
                @foreach ($rx['items'] as $i)
                    <tr class="{{ $i['availability'] !== 'Tersedia' ? 'bg-red-50/60' : '' }}">
                        <td class="font-medium text-slate-900">{{ $i['name'] }}<p class="text-xs font-normal text-slate-500">{{ $i['dose'] }}, {{ strtolower($i['rule']) }}</p>
                            @if ($i['availability'] !== 'Tersedia' && $rx['status'] !== 'Diserahkan')<button class="btn btn-danger btn-sm mt-2" @click="$dispatch('open-modal', { name: 'unavailable', data: { item: '{{ $i['name'] }}', need: '{{ $i['qty'] }} {{ $i['unit'] }}', stock: '{{ $i['stock'] }} {{ $i['unit'] }}' } })"><x-icon name="lucide:circle-alert" class="text-base" /> Tandai Tidak Tersedia</button>@endif</td>
                        <td>{{ $i['qty'] }} {{ $i['unit'] }}</td>
                        <td><x-badge :text="$i['availability']" /><p class="mt-1 text-xs text-slate-500">{{ $i['stock'] }} {{ $i['unit'] }}</p></td></tr>
                @endforeach
                </tbody></table></div>
        </x-card>
        <x-card class="p-5"><h2 class="flex items-center gap-2 font-semibold"><x-icon name="lucide:notebook-pen" class="text-brand-600" /> Catatan dokter</h2><p class="mt-2 text-sm text-slate-600">{{ $rx['note'] }}</p></x-card>
    </div>

    <x-card class="h-fit p-5">
        <h2 class="font-semibold">Proses resep</h2>
        @if ($problem && $rx['status'] !== 'Diserahkan')<p class="mt-2 flex gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-800"><x-icon name="lucide:triangle-alert" class="mt-0.5 text-base" /> Ada obat yang belum tersedia. Kirim catatan ke dokter atau lengkapi stok.</p>@endif
        @php $next = ['Menunggu' => ['Diproses', 'Mulai proses resep'], 'Diproses' => ['Siap', 'Tandai siap diserahkan']][$rx['status']] ?? null; @endphp
        <div class="mt-4 space-y-2">
            @if ($next)
                <form method="POST" action="{{ route('pharmacist.prescriptions.update', $rx['code']) }}">@csrf @method('PATCH')<button name="status" value="{{ $next[0] }}" class="btn btn-primary w-full">{{ $next[1] }}</button></form>
            @endif
            @if ($rx['status'] === 'Siap')<a href="{{ route('pharmacist.handover.show', $rx['code']) }}" class="btn btn-primary w-full">Ke penyerahan obat</a>@endif
            @if ($rx['status'] === 'Menunggu')<form method="POST" action="{{ route('pharmacist.prescriptions.update', $rx['code']) }}">@csrf @method('PATCH')<button name="status" value="Ditolak" class="btn btn-outline w-full text-red-600">Tolak resep</button></form>@endif
            @if ($rx['status'] === 'Diserahkan')<p class="text-sm text-slate-500">Diserahkan {{ $rx['handed_at'] }} oleh {{ $rx['handed_by'] }}.</p>@endif
        </div>
    </x-card>
</div>

{{-- Obat tidak tersedia -> catatan ke dokter --}}
<x-modal name="unavailable" title="Tandai Obat Tidak Tersedia">
    <form method="POST" action="{{ route('pharmacist.prescriptions.unavailable', $rx['code']) }}" class="space-y-4" x-data="{ note: '' }">@csrf
        <input type="hidden" name="item" :value="d.item">
        <p class="text-sm text-slate-500">Tambahkan catatan singkat agar dokter dapat meninjau pengganti obat.</p>
        <div class="rounded-xl bg-slate-50 p-3 text-sm"><p class="font-semibold" x-text="d.item"></p><p class="text-slate-500">Dibutuhkan <span x-text="d.need"></span> - Stok <span x-text="d.stock"></span></p></div>
        <div><label class="label">Catatan untuk dokter *</label><textarea name="note" x-model="note" rows="3" maxlength="200" required class="input" placeholder="Contoh: Stok habis. Mohon pertimbangkan obat pengganti."></textarea><p class="hint text-right"><span x-text="note.length"></span>/200</p></div>
        <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:bell" class="text-base" /> Dikirim ke {{ $rx['doctor'] }}</p>
        <div class="space-y-2"><button class="btn btn-primary w-full"><x-icon name="lucide:send" /> Kirim ke Dokter</button><button type="button" class="btn btn-outline w-full" @click="open = false">Batal</button></div>
    </form>
</x-modal>
@endsection
