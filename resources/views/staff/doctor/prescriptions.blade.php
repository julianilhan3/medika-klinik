@extends('layouts.staff')
@section('title', 'Resep')
@section('crumb', 'Resep')
@section('heading', 'Resep')
@section('subheading', 'Buat resep obat. Resep otomatis diteruskan ke apoteker.')
@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_26rem]">
    <x-card class="p-5 sm:p-6" x-data="{ items: [{ medicine: '', qty: 1, dose: '', rule: '' }] }">
        <h2 class="font-semibold">Resep baru</h2>
        <form method="POST" action="{{ route('doctor.prescriptions.store') }}" class="mt-4 space-y-4">@csrf
            <div><label class="label">Pasien</label>
                <select name="patient" class="input @error('patient') input-error @enderror"><option value="">Pilih pasien</option>@foreach ($patients as $p)<option @selected($prefill === $p['name'])>{{ $p['name'] }}</option>@endforeach</select>
                @error('patient')<p class="err">{{ $message }}</p>@enderror</div>

            <div class="space-y-3">
                <template x-for="(it, i) in items" :key="i">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="grid gap-3 sm:grid-cols-[1fr_6rem]">
                            <div><label class="label">Obat</label><select :name="`items[${i}][medicine]`" x-model="it.medicine" class="input"><option value="">Pilih obat</option>@foreach ($medicines as $m)<option>{{ $m['name'] }}</option>@endforeach</select></div>
                            <div><label class="label">Jumlah</label><input type="number" min="1" :name="`items[${i}][qty]`" x-model="it.qty" class="input"></div>
                            <div><label class="label">Dosis</label><input :name="`items[${i}][dose]`" x-model="it.dose" class="input" placeholder="3x1 tablet"></div>
                            <div class="sm:col-span-2"><label class="label">Aturan pakai</label><input :name="`items[${i}][rule]`" x-model="it.rule" class="input" placeholder="Sesudah makan"></div>
                        </div>
                        <button type="button" x-show="items.length > 1" @click="items.splice(i, 1)" class="mt-3 flex items-center gap-1 text-xs font-medium text-red-600"><x-icon name="lucide:trash-2" class="text-base" /> Hapus obat</button>
                    </div>
                </template>
                @error('items')<p class="err">{{ $message }}</p>@enderror
                <button type="button" @click="items.push({ medicine: '', qty: 1, dose: '', rule: '' })" class="btn btn-soft btn-sm"><x-icon name="lucide:plus" class="text-base" /> Tambah obat</button>
            </div>
            <div class="flex justify-end"><button class="btn btn-primary"><x-icon name="lucide:send" /> Kirim ke apoteker</button></div>
        </form>
    </x-card>

    <x-card class="h-fit">
        <h2 class="p-5 pb-2 font-semibold">Resep terbaru</h2>
        <ul class="divide-y divide-slate-100">
        @foreach ($rows as $r)
            <li class="p-5"><div class="flex items-start justify-between gap-2"><div><p class="text-sm font-semibold">{{ $r['code'] }} &middot; {{ $r['patient'] }}</p><p class="text-xs text-slate-500">{{ $r['date'] }}</p></div><x-badge :text="$r['status']" /></div>
                <ul class="mt-2 space-y-1 text-sm text-slate-600">@foreach ($r['items'] as $i)<li>{{ $i['name'] }} &middot; {{ $i['dose'] }} ({{ $i['qty'] }})</li>@endforeach</ul></li>
        @endforeach
        </ul>
    </x-card>
</div>
@endsection
