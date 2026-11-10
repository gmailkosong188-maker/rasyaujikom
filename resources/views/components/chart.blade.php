{{--
    Grafik batang sederhana tanpa library eksternal.
    Data wajib berasal dari database (labels & values berpasangan).
--}}
@props([
    'labels' => [],
    'values' => [],
    'prefix' => 'Rp',
    'emptyTitle' => 'Belum ada data',
    'emptyDescription' => 'Grafik akan tampil setelah ada transaksi.',
])

@php
    $angka = array_map(fn ($nilai) => (float) $nilai, $values);
    $maks = max($angka ?: [0]);
    $adaData = array_sum($angka) > 0;

    $ringkas = function (float $nilai): string {
        if ($nilai >= 1_000_000_000) {
            return rtrim(rtrim(number_format($nilai / 1_000_000_000, 1, ',', '.'), '0'), ',') . ' M';
        }
        if ($nilai >= 1_000_000) {
            return rtrim(rtrim(number_format($nilai / 1_000_000, 1, ',', '.'), '0'), ',') . ' jt';
        }
        if ($nilai >= 1_000) {
            return number_format($nilai / 1_000, 0, ',', '.') . ' rb';
        }

        return number_format($nilai, 0, ',', '.');
    };
@endphp

@if (! $adaData)
    <x-empty-state :title="$emptyTitle" :description="$emptyDescription" icon="chart" compact />
@else
    <div class="chart">
        <div class="chart-bars" role="img"
            aria-label="Grafik {{ $prefix }} per bulan: {{ implode(', ', $labels) }}">
            @foreach ($labels as $i => $label)
                @php
                    $nilai = $angka[$i] ?? 0;
                    $tinggi = $maks > 0 ? max(3, (int) round(($nilai / $maks) * 100)) : 3;
                @endphp
                <div class="chart-col">
                    <span class="chart-value">{{ $ringkas($nilai) }}</span>
                    <div class="chart-track">
                        <div class="chart-bar" style="height: {{ $tinggi }}%"
                            title="{{ $label }}: {{ $prefix }} {{ number_format($nilai, 0, ',', '.') }}"></div>
                    </div>
                    <span class="chart-label">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <div class="chart-foot">
            <span>Total 6 bulan terakhir</span>
            <strong>{{ $prefix }} {{ number_format(array_sum($angka), 0, ',', '.') }}</strong>
        </div>
    </div>
@endif
