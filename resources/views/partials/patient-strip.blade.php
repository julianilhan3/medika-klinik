@php
    $patient = $profile ?? [
        'name' => session('patient.name', 'Siti Aminah'),
        'rm'   => session('patient.rm', 'RM-001254'),
        'age'  => session('patient.age', '64')
    ];
@endphp

<x-card class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between border border-slate-200 shadow-sm">
    <div class="flex items-center gap-4">
        <span class="grid size-12 place-items-center rounded-full bg-brand-50 text-lg font-bold text-brand-600">
            {{ strtoupper(substr($patient['name'], 0, 1)) }}
        </span>
        <div>
            <p class="font-bold text-slate-900">{{ $patient['name'] }}</p>
            <p class="text-xs text-slate-500">No. Rekam Medis: {{ $patient['rm'] }} &bull; {{ $patient['age'] }} tahun</p>
        </div>
    </div>
    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500 shadow-sm">
        <x-icon name="lucide:shield-check" class="size-4" /> Data medis pribadi
    </span>
</x-card>