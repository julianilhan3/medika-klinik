@extends('layouts.staff')
@section('title', 'Buat Resep')
@section('crumb', 'Antrean pasien / Pemeriksaan / Buat resep')
@section('heading', 'Buat Resep')
@section('subheading', 'Susun e-resep pasien, periksa alergi, lalu kirim ke apotek.')
@section('content')
<div class="mx-auto max-w-5xl space-y-5"
     x-data="{
        meds: @js($medicines), allergies: @js($p['allergies']), q: '', sel: null, f: { dose: '', qty: '', rule: '' }, items: [],
        get results() { const q = this.q.toLowerCase(); return q ? this.meds.filter(m => m.name.toLowerCase().includes(q) || m.category.toLowerCase().includes(q)) : this.meds.slice(0, 6) },
        conflict(m) { return m.class && this.allergies.includes(m.class) },
        pick(m) { this.sel = m; this.f = { dose: '', qty: '', rule: '' } },
        get canAdd() { return this.sel && !this.conflict(this.sel) && this.sel.stock > 0 && this.f.dose && this.f.qty > 0 && this.f.rule },
        add() { this.items.push({ ...this.f, name: this.sel.name, unit: this.sel.unit.toLowerCase(), form: this.sel.form }); this.sel = null; this.q = ''; this.f = { dose: '', qty: '', rule: '' } },
        get conflicts() { return this.items.filter(i => { const m = this.meds.find(x => x.name === i.name); return m && this.conflict(m) }) },
        get blocked() { return this.items.length === 0 || this.conflicts.length > 0 }
     }">
    @include('staff.doctor._patient-head')

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([['Pemeriksaan', 'Tersimpan', 'done'], ['Buat Resep', 'Sedang diisi', 'now'], ['Apotek', 'Belum dikirim', 'todo']] as $i => [$t, $d, $st])
            <div class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70"><span class="grid size-8 place-items-center rounded-full text-sm font-semibold {{ $st === 'todo' ? 'bg-slate-100 text-slate-400' : 'bg-brand-600 text-white' }}">@if ($st === 'done')<x-icon name="lucide:check" class="text-base" />@else{{ $i + 1 }}@endif</span><div class="leading-tight"><p class="text-sm font-semibold">{{ $t }}</p><p class="text-xs text-slate-500">{{ $d }}</p></div></div>
        @endforeach
    </div>

    <template x-if="conflicts.length">
        <div class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><x-icon name="lucide:triangle-alert" class="mt-0.5 text-xl" />
            <div><p>Pasien memiliki alergi {{ is_string($p['allergies']) ? $p['allergies'] : implode(', ', $p['allergies']) }}. Hapus obat pemicu alergi; resep tidak dapat dikirim sebelum konflik diperbaiki.</p></div></div>
    </template>

    <div class="grid gap-5 lg:grid-cols-[22rem_1fr]">
        <x-card class="p-5">
            <h2 class="font-semibold">Cari obat</h2><p class="text-sm text-slate-500">Pilih obat dari katalog apotek.</p>
            <div class="relative mt-3"><x-icon name="lucide:search" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" /><input x-model="q" class="input pl-10" placeholder="Cari nama atau kategori obat"></div>
            <p class="mt-2 text-xs text-slate-500" x-text="q ? results.length + ' hasil untuk &quot;' + q + '&quot;' : 'Obat terdaftar'"></p>
            <ul class="mt-2 max-h-80 space-y-2 overflow-y-auto">
                <template x-for="m in results" :key="m.id">
                    <li><button type="button" @click="pick(m)" class="w-full rounded-xl border p-3 text-left transition" :class="conflict(m) ? 'border-red-200 bg-red-50' : (sel && sel.id === m.id ? 'border-brand-600 bg-brand-50' : 'border-slate-200 hover:bg-slate-50')">
                        <p class="text-sm font-semibold" :class="conflict(m) && 'text-red-700'" x-text="m.name"></p>
                        <p class="text-xs text-slate-500" x-text="m.form + (m.class ? ' - golongan ' + m.class.toLowerCase() : '')"></p>
                        <p class="mt-1 text-xs" :class="m.stock > 0 ? 'text-emerald-700' : 'text-red-600'" x-text="m.stock > 0 ? 'Stok: ' + m.stock + ' ' + m.unit.toLowerCase() : 'Stok habis'"></p>
                        <p x-show="conflict(m)" class="mt-1 text-xs font-semibold text-red-700">Konflik alergi</p></button></li>
                </template>
            </ul>
        </x-card>

        <x-card class="p-5">
            <div class="flex items-center justify-between"><h2 class="font-semibold">Detail obat</h2><span x-show="sel && conflict(sel)" x-cloak class="flex items-center gap-1 text-xs font-semibold text-red-600"><x-icon name="lucide:triangle-alert" class="text-sm" /> Konflik alergi</span></div>
            <div x-show="!sel" class="py-10 text-center text-sm text-slate-500">Pilih obat dari daftar di kiri.</div>
            <div x-show="sel" x-cloak>
                <div class="mt-3 rounded-xl p-3" :class="sel && conflict(sel) ? 'bg-red-50' : 'bg-slate-50'"><p class="font-semibold" x-text="sel && sel.name"></p>
                    <p class="text-sm" :class="sel && conflict(sel) ? 'text-red-700' : 'text-slate-500'" x-text="sel && (conflict(sel) ? 'Golongan ' + sel.class.toLowerCase() + ' - tidak dapat diresepkan untuk pasien ini.' : sel.category)"></p></div>
                <p x-show="sel && sel.stock <= 0" class="mt-2 text-sm text-red-600">Stok obat habis. Pilih obat pengganti.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div><label class="label">Dosis</label><input x-model="f.dose" class="input" placeholder="Contoh: 5 mg, 1 tablet"></div>
                    <div><label class="label">Jumlah</label><input x-model="f.qty" type="number" min="1" class="input" placeholder="Contoh: 30"></div>
                    <div class="sm:col-span-2"><label class="label">Aturan pakai</label><input x-model="f.rule" class="input" placeholder="Contoh: 3x sehari sesudah makan"></div>
                </div>
                <button type="button" @click="add()" :disabled="!canAdd" class="btn btn-primary mt-4 w-full"><x-icon name="lucide:plus" /> Tambahkan ke resep</button>
            </div>
        </x-card>
    </div>

    <form method="POST" action="{{ route('doctor.rx.store', $p['no']) }}">@csrf
        <x-card>
            <div class="flex items-center justify-between p-5 pb-3"><h2 class="font-semibold">Ringkasan resep</h2><span class="text-xs text-slate-500" x-text="items.length + ' obat'"></span></div>
            <div class="overflow-x-auto"><table class="tbl min-w-[36rem]">
                <thead><tr><th>Obat</th><th>Dosis</th><th>Jumlah</th><th>Aturan pakai</th><th>Validasi</th><th></th></tr></thead>
                <tbody>
                    <template x-for="(it, i) in items" :key="i">
                        <tr><td class="font-medium text-slate-900"><span x-text="it.name"></span><p class="text-xs font-normal text-slate-400" x-text="it.form"></p>
                                <input type="hidden" :name="`items[${i}][medicine]`" :value="it.name"><input type="hidden" :name="`items[${i}][dose]`" :value="it.dose"><input type="hidden" :name="`items[${i}][qty]`" :value="it.qty"><input type="hidden" :name="`items[${i}][rule]`" :value="it.rule"></td>
                            <td x-text="it.dose"></td><td><span x-text="it.qty"></span> <span x-text="it.unit"></span></td><td x-text="it.rule"></td>
                            <td><span class="flex items-center gap-1 text-xs font-medium text-emerald-700"><x-icon name="lucide:check-circle-2" class="text-base" /> Siap</span></td>
                            <td class="text-right"><button type="button" @click="items.splice(i, 1)" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Hapus obat"><x-icon name="lucide:trash-2" /></button></td></tr>
                    </template>
                    <tr x-show="items.length === 0"><td colspan="6" class="py-8 text-center text-sm text-slate-500">Belum ada obat dalam resep.</td></tr>
                </tbody></table></div>
            @error('items')<p class="err px-5">{{ $message }}</p>@enderror
            <div class="space-y-3 p-5 pt-3">
                <div><label class="label">Catatan untuk apoteker (opsional)</label><textarea name="note" rows="2" class="input" placeholder="Contoh: Pemakaian rutin selama 30 hari.">{{ old('note') }}</textarea></div>
                <button class="btn btn-primary w-full" :disabled="blocked"><x-icon name="lucide:send" x-show="!blocked" /><x-icon name="lucide:lock" x-show="blocked" x-cloak /> Kirim ke Apotek</button>
                <a href="{{ route('doctor.exam', $p['no']) }}" class="flex items-center justify-center gap-1.5 text-sm font-medium text-slate-600 hover:text-brand-600"><x-icon name="lucide:arrow-left" class="text-base" /> Kembali ke Pemeriksaan</a>
            </div>
        </x-card>
    </form>
    <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon name="lucide:lock" class="text-base" /> Data resep tersimpan aman dalam rekam medis pasien.</p>
</div>
@endsection
