{{-- Buka dengan: @click="$dispatch('open-modal', { name: 'nama', data: {...} })" ; data tersedia sebagai `d` --}}
@props(['name', 'title', 'width' => 'max-w-lg'])
<div x-data="{ open: false, d: {} }"
     x-on:open-modal.window="if ($event.detail.name === '{{ $name }}') { d = $event.detail.data || {}; open = true }"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center overflow-y-auto p-4">
    <div class="fixed inset-0 bg-slate-900/50" @click="open = false"></div>
    <div x-show="open" x-transition class="relative w-full {{ $width }} rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-start justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
            <button type="button" @click="open = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100" aria-label="Tutup"><x-icon name="lucide:x" class="text-xl" /></button>
        </div>
        {{ $slot }}
    </div>
</div>
