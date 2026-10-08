<div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
    <x-stat label="Total pasien" :value="$stats['total']" icon="lucide:users" />
    <x-stat label="Menunggu" :value="$stats['waiting']" icon="lucide:clock" tone="amber" />
    <x-stat label="Dipanggil" :value="$stats['called']" icon="lucide:volume-2" tone="blue" />
    <x-stat label="Selesai" :value="$stats['done']" icon="lucide:check-circle-2" tone="green" />
    <x-stat label="Tidak hadir" :value="$stats['absent']" icon="lucide:user-x" tone="red" />
</div>

@if ($next)
@php $called = $next['status'] === 'Dipanggil' || session('called') === $next['no']; @endphp
<x-card class="mt-5 p-5 sm:p-6">
    <div class="flex items-center justify-between"><h2 class="font-semibold">Pasien berikutnya</h2><span class="flex items-center gap-1.5 text-sm text-slate-500"><x-icon name="lucide:building-2" class="text-base" /> Poli Umum - Ruang 01</span></div>
    <div class="mt-4 grid gap-5 lg:grid-cols-[1fr_20rem] lg:items-center">
        <div class="flex flex-wrap items-center gap-5">
            <div class="rounded-xl bg-brand-50 px-6 py-4 text-center"><p class="text-xs text-slate-500">Antrean</p><p class="text-3xl font-bold text-brand-600">{{ $next['no'] }}</p></div>
            <div class="text-sm text-slate-600">
                <p class="flex items-center gap-2 text-xl font-bold text-slate-900">{{ $next['patient'] }} <x-badge :text="$called ? 'Dipanggil' : 'Menunggu'" /></p>
                <p>{{ $next['age'] }} tahun - {{ $next['gender'] }} - {{ $next['rm'] }}</p>
                <p class="mt-1"><b class="text-slate-700">Keluhan:</b> {{ $next['complaint'] }}</p>
                <p class="text-xs text-slate-500">Terdaftar {{ $next['registered'] }} WIB</p>
                @if ($next['allergies'])<p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700"><x-icon name="lucide:triangle-alert" class="text-sm" /> Alergi: {{ implode(', ', $next['allergies']) }}</p>@endif
            </div>
        </div>
        <div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('doctor.queue.call', $next['no']) }}" class="flex-1">@csrf
                    <button class="btn w-full {{ $called ? 'btn-outline' : 'btn-primary' }}"><x-icon name="lucide:volume-2" /> {{ $called ? 'Panggil ulang' : 'Panggil' }}</button></form>
                <form method="POST" action="{{ route('doctor.queue.start', $next['no']) }}" class="flex-1">@csrf
                    <button class="btn btn-primary w-full" @disabled(! $called)><x-icon name="lucide:play" /> Mulai</button></form>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $called ? 'Pasien sudah dipanggil. Tekan Mulai saat pasien berada di ruang periksa.' : 'Panggilan akan ditampilkan di layar ruang tunggu. Pemeriksaan dimulai setelah Anda menekan Mulai.' }}</p>
        </div>
    </div>
</x-card>
@endif

<x-card class="mt-5">
    <div class="flex items-start justify-between gap-3 p-5 pb-3"><div><h2 class="font-semibold">Daftar antrean hari ini</h2><p class="text-sm text-slate-500">{{ count($rows) }} pasien - Diurutkan berdasarkan nomor antrean</p></div>
        <a href="{{ request()->fullUrl() }}" class="btn btn-outline btn-sm"><x-icon name="lucide:refresh-cw" /> Perbarui</a></div>
    <div class="overflow-x-auto"><table class="tbl min-w-[40rem]">
        <thead><tr><th>No Antrean</th><th>Nama</th><th>Umur</th><th>Keluhan awal</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody>
        @foreach ($rows as $p)
            <tr class="{{ $next && $next['no'] === $p['no'] ? 'bg-brand-50/60' : '' }}">
                <td class="font-semibold {{ $next && $next['no'] === $p['no'] ? 'text-brand-600' : '' }}">{{ $p['no'] }}</td>
                <td class="font-medium text-slate-900">{{ $p['patient'] }}<p class="text-xs font-normal text-slate-400">{{ $p['rm'] }}</p></td>
                <td>{{ $p['age'] }} tahun</td><td>{{ $p['complaint'] }}</td><td><x-badge :text="$p['status']" /></td>
                <td>
                    @if (in_array($p['status'], ['Menunggu', 'Dipanggil']))
                        <form method="POST" action="{{ route('doctor.queue.absent', $p['no']) }}" onsubmit="return confirm('Tandai {{ $p['patient'] }} tidak hadir?')">@csrf<button class="btn btn-outline btn-sm text-red-600">Tidak Hadir</button></form>
                    @else<span class="text-xs text-slate-300">Tidak Hadir</span>@endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <p class="flex items-center gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500"><x-icon name="lucide:info" class="text-base" /> Tombol Tidak Hadir tersedia untuk pasien yang belum selesai diperiksa.</p>
</x-card>
