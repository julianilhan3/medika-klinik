@extends('layouts.staff')
@section('title', 'Daftarkan Pasien')
@section('crumb', 'Pendaftaran / Pasien langsung')
@section('heading', 'Daftarkan Pasien Langsung')
@section('subheading', 'Daftarkan pasien walk-in dan tandai kedatangan dengan cepat, tanpa membuat akun pasien.')
@section('content')
@if (session('ticket'))
    @php $t = session('ticket'); @endphp
    <x-card class="mx-auto max-w-lg p-8 text-center">
        <span class="mx-auto grid size-14 place-items-center rounded-full bg-emerald-50 text-emerald-600"><x-icon name="lucide:check" class="text-3xl" /></span>
        <h2 class="mt-4 text-xl font-bold">Pendaftaran berhasil</h2>
        <p class="text-sm text-slate-500">Nomor antrean</p>
        <p class="my-2 text-6xl font-bold text-brand-600">{{ $t['queue'] }}</p>
        <p class="font-semibold">{{ $t['name'] }}</p>
        <p class="text-sm text-slate-500">{{ $t['poli'] }} &middot; {{ $t['doctor'] }}</p>
        <div class="mt-6 grid gap-2 sm:grid-cols-2"><button onclick="window.print()" class="btn btn-primary"><x-icon name="lucide:printer" /> Cetak</button><a href="{{ route('admin.patients.create') }}" class="btn btn-outline">Daftarkan pasien baru</a></div>
    </x-card>
@else
<form method="POST" action="{{ route('admin.patients.store') }}" class="grid gap-6 xl:grid-cols-[1fr_20rem]"
      x-data="{
    step: 1,
    nik: '{{ old('nik') }}',
    p: {
        name: '{{ old('name') }}',
        birth: '{{ old('birth') }}',
        gender: '{{ old('gender') }}',
        phone: '{{ old('phone') }}',
        address: '{{ old('address') }}'
    },
    poli: '{{ old('poli') }}',
    doctor: '{{ old('doctor') }}',
    date: '{{ old('date', now()->toDateString()) }}',
    time: '{{ old('time') }}',
    found: null,
    nikErr: '',
    slots: [],
    doctors: @js($doctors),

    get list() {
        return this.doctors.filter(
            d => d.poli === this.poli && d.status === 'Aktif'
        );
    },

    async search() {
        this.nikErr = '';
        this.found = null;

        if (!/^\d{16}$/.test(this.nik)) {
            this.nikErr = 'NIK harus 16 angka.';
            return;
        }

        const r = await fetch(
            '{{ route('admin.patients.lookup') }}?nik=' + this.nik,
            {
                headers: {
                    Accept: 'application/json'
                }
            }
        );

        const d = await r.json();

        this.found = d ? 'yes' : 'no';

        if (d) {
            this.p = {
                name: d.name,
                birth: d.birth,
                gender: d.gender,
                phone: d.phone,
                address: d.address
            };
        }
    },

    async loadSlots() {
        this.time = '';
        this.slots = [];

        if (!this.doctor || !this.date) {
            return;
        }

        const r = await fetch(
            '{{ route('admin.patients.slots') }}?doctor=' +
            encodeURIComponent(this.doctor) +
            '&date=' +
            encodeURIComponent(this.date),
            {
                headers: {
                    Accept: 'application/json'
                }
            }
        );

        if (!r.ok) {
            return;
        }

        this.slots = await r.json();
    },

    init() {
        this.$watch('doctor', () => this.loadSlots());
        this.$watch('date', () => this.loadSlots());
    }
}">
    @csrf
    <input type="hidden" name="poli" :value="poli"><input type="hidden" name="doctor" :value="doctor"><input type="hidden" name="time" :value="time">

    <x-card class="p-5 sm:p-7">
        <ol class="mb-6 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            @foreach (['Data Pasien', 'Pilih Jadwal', 'Konfirmasi'] as $i => $l)
                <li class="flex items-center gap-2" :class="step >= {{ $i + 1 }} ? 'font-semibold text-brand-600' : 'text-slate-400'">
                    <span :class="step >= {{ $i + 1 }} ? 'bg-brand-600 text-white' : 'bg-slate-100'" class="grid size-6 place-items-center rounded-full text-xs">{{ $i + 1 }}</span>{{ $l }}</li>
            @endforeach
        </ol>

        <div x-show="step === 1" class="space-y-4">
            <h2 class="font-semibold">Cari data pasien</h2>
            <div><label class="label">Nomor Induk Kependudukan (NIK) *</label>
                <div class="flex gap-2"><input x-model="nik" maxlength="16" inputmode="numeric" name="nik" class="input" :class="nikErr && 'input-error'" placeholder="16 digit NIK"><button type="button" @click="search()" class="btn btn-outline shrink-0">Cari pasien</button></div>
                <p x-show="nikErr" x-text="nikErr" class="err"></p></div>
            <div x-show="found === 'yes'" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">Pasien ditemukan. Periksa kembali data sebelum melanjutkan.</div>
            <div x-show="found === 'no'" class="rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm text-brand-700">Pasien belum terdaftar. Lengkapi data di bawah untuk registrasi cepat.</div>
            <div x-show="found" class="grid gap-4 sm:grid-cols-2">
                <x-field label="Nama lengkap *" name="name" model="p.name" class="sm:col-span-2" />
                <x-field label="Tanggal lahir *" name="birth" type="date" model="p.birth" />
                <div><label class="label">Jenis kelamin *</label><select name="gender" x-model="p.gender" class="input"><option value="">Pilih</option><option>Laki-laki</option><option>Perempuan</option></select></div>
                <x-field label="Nomor telepon *" name="phone" type="tel" model="p.phone" />
                <x-field label="Alamat *" name="address" model="p.address" />
            </div>
            <div class="flex justify-end gap-2"><a href="{{ route('admin.patients.index') }}" class="btn bg-red-50 text-red-600 hover:bg-red-100">Batalkan</a>
                <button type="button" class="btn btn-primary" :disabled="!found || !p.name || !p.phone" @click="step = 2">Lanjut</button></div>
        </div>

        <div x-show="step === 2" x-cloak class="space-y-4">
            <h2 class="font-semibold">Pilih jadwal kunjungan</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Poli *</label><select x-model="poli" @change="doctor = ''" class="input"><option value="">Pilih poli</option>@foreach ($poli as $x)<option>{{ $x['name'] }}</option>@endforeach</select></div>
                <div><label class="label">Dokter *</label><select x-model="doctor" :disabled="!poli" class="input"><option value="">Pilih dokter</option><template x-for="d in list" :key="d.id">
    <option :value="d.id" x-text="d.name"></option>
</template></select></div>
                <x-field label="Tanggal kunjungan *" name="date" type="date" model="date" class="sm:col-span-2" />
            </div>
           <div>
    <label class="label">Slot waktu *</label>

    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">

        <template x-for="s in slots" :key="s.time">
            <button
                type="button"
                :disabled="!s.available"
                @click="time = s.time"
                :class="time === s.time
                    ? 'border-brand-600 bg-brand-50 ring-2 ring-brand-600/20'
                    : 'border-slate-200 bg-white'"
                class="rounded-lg border px-3 py-2 text-left text-sm disabled:bg-slate-50 disabled:text-slate-300"
            >
                <span
                    class="block font-medium"
                    x-text="s.time"
                ></span>

                <span
                    class="text-xs text-slate-500"
                    x-text="s.available
                        ? s.left + ' tempat tersedia'
                        : 'Tidak tersedia'"
                ></span>
            </button>
        </template>

        <div
            x-show="!slots.length && doctor && date"
            class="col-span-full rounded-lg bg-slate-50 p-3 text-sm text-slate-500"
        >
            Tidak ada slot tersedia untuk dokter dan tanggal yang dipilih.
        </div>

    </div>
</div>
            <div class="flex justify-between"><button type="button" class="btn btn-outline" @click="step = 1">Kembali</button><button type="button" class="btn btn-primary" :disabled="!poli || !doctor || !date || !time" @click="step = 3">Pilih slot ini</button></div>
        </div>

        <div x-show="step === 3" x-cloak>
            <h2 class="font-semibold">Periksa dan konfirmasi</h2>
            <dl class="mt-3 divide-y divide-slate-100 rounded-xl bg-slate-50 px-5 text-sm">
                <template x-for="[k, v] in [['Nama', p.name], ['NIK', nik], ['Telepon', p.phone], ['Poli', poli], ['Dokter', doctor], ['Tanggal', date], ['Jam', time]]" :key="k">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500" x-text="k"></dt><dd class="text-right font-medium" x-text="v"></dd></div></template>
            </dl>
            <div class="mt-6 flex justify-between"><button type="button" class="btn btn-outline" @click="step = 2">Kembali</button><button class="btn btn-primary"><x-icon name="lucide:check" /> Konfirmasi</button></div>
        </div>
    </x-card>

    <x-card class="h-fit p-5 text-sm">
        <h3 class="font-semibold">Ringkasan kunjungan</h3>
        <dl class="mt-3 space-y-2.5 text-slate-600">
            <div class="flex justify-between"><dt>Jenis pendaftaran</dt><dd class="font-medium text-slate-900">Walk-in</dd></div>
            <div class="flex justify-between"><dt>Pasien</dt><dd class="font-medium text-slate-900" x-text="p.name || '-'"></dd></div>
            <div class="flex justify-between"><dt>Poli</dt><dd class="font-medium text-slate-900" x-text="poli || '-'"></dd></div>
            <div class="flex justify-between"><dt>Jam</dt><dd class="font-medium text-slate-900" x-text="time || '-'"></dd></div>
        </dl>
        <p class="mt-4 flex gap-2 text-xs text-slate-500"><x-icon name="lucide:info" class="mt-0.5" /> Pelayanan ramah lansia: pasien dengan usia lanjut dapat diarahkan ke antrean prioritas.</p>
    </x-card>
</form>
@endif
@endsection
