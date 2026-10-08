@extends('layouts.staff')
@section('title', 'Laporan')
@section('crumb', 'Laporan')
@section('heading', 'Laporan')
@section('subheading', 'Ringkasan kunjungan dan layanan klinik.')
@section('actions')
<div class="flex gap-2">
    <select class="input w-auto">
        <option>Oktober 2026</option>
        <option>September 2026</option>
    </select>
    <button class="btn btn-outline">
        <x-icon name="lucide:download" /> Unduh
    </button>
</div>
@endsection
@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Total kunjungan" :value="$r['summary']['visits']" icon="lucide:users" />
    <x-stat label="Pasien baru" :value="$r['summary']['new_patients']" icon="lucide:user-plus" tone="blue" />
    <x-stat label="Resep diterbitkan" :value="$r['summary']['prescriptions']" icon="lucide:file-text" tone="green" />
    <x-stat label="Tingkat pembatalan" :value="$r['summary']['cancel_rate']" icon="lucide:x-circle" tone="red" />
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <!-- Card Grafik Chart.js -->
    <x-card class="p-5">
        <h2 class="mb-4 font-semibold text-slate-900">Kunjungan per bulan</h2>
        <div class="relative h-64 w-full">
            <canvas id="kunjunganChart"></canvas>
        </div>
    </x-card>
    
    <x-card>
        <h2 class="p-5 pb-3 font-semibold">Kunjungan per poli</h2>
        <div class="overflow-x-auto">
            <table class="tbl w-full">
                <thead>
                    <tr>
                        <th>Poli</th>
                        <th>Kunjungan</th>
                        <th>Selesai</th>
                        <th>Batal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($r['by_poli'] as $p)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $p['poli'] }}</td>
                            <td>{{ $p['visits'] }}</td>
                            <td>{{ $p['done'] }}</td>
                            <td>{{ $p['cancel'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>

<!-- Muat Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('kunjunganChart').getContext('2d');
        
        // Konversi data array PHP bulanan ke format JavaScript
        const labels = {!! json_encode(array_keys($r['monthly'])) !!};
        const dataValues = {!! json_encode(array_values($r['monthly'])) !!};

        new Chart(ctx, {
            // Ubah 'bar' menjadi 'line' jika Anda lebih suka grafik garis yang menyambung ala trading
            type: 'bar', 
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Kunjungan',
                    data: dataValues,
                    backgroundColor: '#2563eb', // Warna biru brand-600
                    hoverBackgroundColor: '#1d4ed8', // Biru lebih gelap saat disentuh
                    borderRadius: 6, // Ujung batang melengkung elegan
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }, // Sembunyikan label legenda agar bersih
                    tooltip: {
                        backgroundColor: '#1e293b', // Tooltip warna gelap modern
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }, // Hindari angka desimal di sumbu Y
                        grid: {
                            color: '#f1f5f9', // Garis bantu tipis
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: { display: false } // Hilangkan garis vertikal
                    }
                }
            }
        });
    });
</script>
@endsection