{{-- Kalender mingguan (Tanpa foreach, strict variables) --}}
@php
    /** @var array|null $sessions */
    $sessions = $sessions ?? [];
    /** @var \Carbon\Carbon|null $weekStart */
    $weekStart = $weekStart ?? now()->startOfWeek();

    $h0 = 8;
    $h1 = 18;
    $px = 64;
    $names = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    
    // Fungsi pembantu untuk konversi jam ke angka desimal
    $toH = fn ($t) => (int) substr((string) ($t ?? '00:00'), 0, 2) + ((int) substr((string) ($t ?? '00:00'), 3, 2)) / 60;
@endphp

<div class="overflow-x-auto">
    <div class="min-w-[44rem]">
        
        {{-- Header Hari --}}
        <div class="grid grid-cols-[3.5rem_repeat(7,minmax(0,1fr))] border-b border-slate-200">
            <div class="px-2 py-3 text-xs text-slate-500">WIB</div>
            @for ($i = 0; $i < 7; $i++)
                @php 
                    $n = (string) $names[$i];
                    $date = $weekStart->copy()->addDays($i); 
                    $isToday = (bool) $date->isToday(); 
                @endphp
                <div class="px-2 py-2 text-center {{ $isToday ? 'text-brand-600' : 'text-slate-500' }}">
                    <p class="text-xs">{{ $n }}</p>
                    <p class="text-sm font-semibold {{ $isToday ? '' : 'text-slate-800' }}">{{ $date->format('j') }} {{ $date->translatedFormat('M') }}</p>
                </div>
            @endfor
        </div>

        {{-- Grid Kalender --}}
        <div class="grid grid-cols-[3.5rem_repeat(7,minmax(0,1fr))]">
            
            {{-- Kolom Jam (Kiri) --}}
            <div>
                @for ($h = $h0; $h < $h1; $h++)
                    <div class="px-2 text-right text-xs text-slate-400" style="height: {{ $px }}px">{{ sprintf('%02d.00', $h) }}</div>
                @endfor
            </div>

            {{-- Kolom Sesi per Hari --}}
            @for ($i = 0; $i < 7; $i++)
                @php 
                    // Ambil sesi untuk hari ke-$i dan reset index array-nya (values()->all())
                    $daySessions = collect($sessions)->where('day', $i)->values()->all();
                    $totalSessions = count($daySessions);
                @endphp
                
             <div x-data="{ h: {{ ($h1 -$h0) * $px }}, px: {{$px }} }" 
     class="relative border-l border-slate-100" 
     x-bind:style="`height: ${h}px; background-image: linear-gradient(to bottom, #f1f5f9 1px, transparent 1px); background-size: 100% ${px}px;`">
                    
                    @if ($totalSessions === 0)
                        {{-- Jika hari Minggu kosong, tampilkan icon --}}
                        @if ($i === 6)
                            <p class="absolute inset-x-0 top-1/3 px-2 text-center text-xs text-slate-400">
                                <x-icon name="lucide:coffee" class="mb-1 block text-lg mx-auto" />
                                Tidak ada praktik
                            </p>
                        @endif
                    @else
                        {{-- Render kotak sesi jadwal menggunakan for loop --}}
                        @for ($j = 0; $j < $totalSessions; $j++)
                            @php
                                // Casting ketat seperti queue-live
                                $s = $daySessions[$j] ?? [];
                                $start  = (string) ($s['start'] ?? '00:00');
                                $end    = (string) ($s['end'] ?? '00:00');
                                $poli   = (string) ($s['poli'] ?? 'Poli Umum');
                                $quota  = (int) ($s['quota'] ?? 0);
                                $booked = (int) ($s['booked'] ?? 0);
                                
                                // Kalkulasi posisi CSS
                                $top = ($toH($start) - $h0) * $px + 2;
                                $height = max(0, ($toH($end) - $toH($start)) * $px - 4);
                            @endphp
                            
                            <div class="absolute inset-x-1 overflow-hidden rounded-lg border-l-4 border-brand-600 bg-brand-50 p-2 text-xs text-brand-700"
                                 style="top: {{ $top }}px; height: {{ $height }}px">
                                <p class="font-semibold">{{ $start }}-{{ $end }}</p>
                                <p class="mt-0.5 leading-tight text-slate-700">{{ $poli }}</p>
                                <p class="mt-1 text-slate-500">Kuota {{ $quota }}</p>
                                <p class="font-semibold">{{ $booked }} reservasi</p>
                            </div>
                        @endfor
                    @endif

                </div>
            @endfor

        </div>
    </div>
</div>

{{-- Keterangan --}}
<div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
    <span class="flex items-center gap-4">
        <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-brand-600"></span> Jam praktik</span>
        <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-slate-500"></span> Cuti</span>
    </span>
    <span>Semua jam dalam WIB</span>
</div>