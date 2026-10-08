@props(['text', 'tone' => null])
@php
    $map = [
        'green' => ['Selesai', 'Diserahkan', 'Aktif', 'Disetujui', 'Siap', 'Check-in', 'Tersedia', 'Sudah check-in', 'Siap diambil'],
        'amber' => ['Menunggu', 'Menunggu Apoteker', 'Menunggu Persetujuan', 'Menipis', 'Perlu Konfirmasi', 'Kurang'],
        'blue'  => ['Sedang Diperiksa', 'Dilayani', 'Dipanggil', 'Diperiksa', 'Diproses'],
        'red'   => ['Batal', 'Dibatalkan', 'Ditolak', 'Habis', 'Kedaluwarsa', 'Nonaktif', 'Tidak Hadir', 'Tidak Tersedia'],
    ];
    $tone ??= collect($map)->search(fn ($list) => in_array($text, $list)) ?: 'slate';
    $c = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'blue'  => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'red'   => 'bg-red-50 text-red-700 ring-red-600/20',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ][$tone];
@endphp
<span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $c }}">
    <span class="size-1.5 rounded-full bg-current"></span>{{ $text }}
</span>
