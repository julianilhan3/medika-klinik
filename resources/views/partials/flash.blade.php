@if (session('status'))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
        <x-icon name="lucide:check-circle-2" class="mt-0.5 text-lg" /><p>{{ session('status') }}</p>
    </div>
@endif
@if (session('error'))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <x-icon name="lucide:alert-circle" class="mt-0.5 text-lg" /><p>{{ session('error') }}</p>
    </div>
@endif
@if ($errors->any() && ! ($hideErrors ?? false))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <x-icon name="lucide:alert-circle" class="mt-0.5 text-lg" />
        <div><p class="font-semibold">Data belum dapat disimpan</p><p>Perbaiki isian yang ditandai di bawah ini.</p></div>
    </div>
@endif
