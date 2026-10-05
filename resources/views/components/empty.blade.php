@props(['icon' => 'lucide:inbox', 'title', 'text' => null])
<div class="flex flex-col items-center px-6 py-14 text-center">
    <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600"><x-icon :name="$icon" class="text-3xl" /></span>
    <p class="mt-4 font-semibold text-slate-800">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $text }}</p>@endif
    {{ $slot }}
</div>
