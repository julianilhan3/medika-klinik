@php $steps = $steps ?? ['Pilih jadwal', 'Data pasien', 'Konfirmasi']; @endphp
<ol class="flex items-center gap-2 rounded-xl bg-sky-50 px-4 py-3 text-xs sm:text-sm">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; $state = $n < $current ? 'done' : ($n === $current ? 'now' : 'next'); @endphp
        <li class="flex items-center gap-2 {{ $loop->last ? '' : 'flex-1' }}">
            <span class="grid size-6 shrink-0 place-items-center rounded-full text-xs font-semibold transition
                {{ $state === 'now' ? 'bg-brand-600 text-white' : ($state === 'done' ? 'bg-brand-50 text-brand-600' : 'bg-white text-slate-500 ring-1 ring-slate-200') }}">
                @if ($state === 'done') <x-icon name="lucide:check" /> @else {{ $n }} @endif
            </span>
            <span class="hidden sm:inline {{ $state === 'now' ? 'font-semibold text-brand-600' : 'text-slate-500' }}">{{ $label }}</span>
            @unless ($loop->last)<span class="h-px flex-1 {{ $state === 'done' ? 'bg-brand-600' : 'bg-slate-200' }}"></span>@endunless
        </li>
    @endforeach
</ol>