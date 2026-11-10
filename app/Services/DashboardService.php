<?php

namespace App\Services;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Query statistik dashboard.
 *
 * SEMUA angka berasal dari database (tidak ada data hardcoded).
 * Setiap method menerima batas akses secara eksplisit sehingga data
 * pemilik/penyewa tidak pernah tercampur dengan milik user lain.
 */
class DashboardService
{
    /**
     * Ringkasan untuk admin: data seluruh sistem.
     *
     * @return array<string, mixed>
     */
    public function admin(): array
    {
        // Satu query agregat untuk menghitung motor per status (hindari 5 query).
        $motorPerStatus = Motor::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalDibayar = (float) Transaksi::query()
            ->where('status', Transaksi::STATUS_DIBAYAR)
            ->sum('jumlah');

        return [
            'jumlahUser' => User::count(),
            'jumlahMotor' => (int) $motorPerStatus->sum(),
            'motorTersedia' => (int) ($motorPerStatus[Motor::STATUS_TERSEDIA] ?? 0),
            'motorDisewa' => (int) ($motorPerStatus[Motor::STATUS_DISEWA] ?? 0),
            'motorPending' => (int) ($motorPerStatus[Motor::STATUS_PENDING] ?? 0),
            'motorPerawatan' => (int) ($motorPerStatus[Motor::STATUS_PERAWATAN] ?? 0),
            'motorDitolak' => (int) ($motorPerStatus[Motor::STATUS_DITOLAK] ?? 0),
            'jumlahPenyewaan' => Penyewaan::count(),
            'penyewaanAktif' => Penyewaan::aktif()->count(),
            'totalPembayaran' => $totalDibayar,
            // Bagian pemilik/admin HANYA dihitung sebagai diterima bila dana
            // sudah benar-benar dicairkan admin (settled_at terisi).
            'bagianPemilik' => (float) BagiHasil::query()
                ->sudahDicairkan()
                ->sum('nominal_pemilik'),
            'bagianAdmin' => (float) BagiHasil::query()
                ->sudahDicairkan()
                ->sum('nominal_admin'),
            // Dana yang masih di tangan admin, belum diserahkan ke pemilik.
            'danaMenungguPencairan' => (float) BagiHasil::query()
                ->menungguPencairan()
                ->sum('nominal_pemilik'),
            'jumlahMenungguPencairan' => BagiHasil::query()->menungguPencairan()->count(),
            'bagiHasilTercatat' => BagiHasil::count(),
            'motorTerbaru' => Motor::with(['pemilik', 'tarifRental'])
                ->latest()
                ->limit(5)
                ->get(),
            'penyewaanTerbaru' => Penyewaan::query()
                ->with(['penyewa', 'motor', 'transaksis'])
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'motorMenungguVerifikasi' => Motor::query()
                ->where('status', Motor::STATUS_PENDING)
                ->with(['pemilik', 'tarifRental'])
                ->latest()
                ->limit(5)
                ->get(),
            'grafikPendapatan' => $this->grafikTransaksi(),
        ];
    }

    /**
     * Ringkasan untuk pemilik: HANYA motor & penyewaan miliknya.
     *
     * @return array<string, mixed>
     */
    public function pemilik(User $pemilik): array
    {
        $motorPerStatus = $pemilik->motors()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Pendapatan pemilik = HANYA dana yang sudah dicairkan admin.
        // Dana menunggu pencairan dipisah supaya tidak dihitung sebagai diterima.
        $pendapatan = (float) DB::table('bagi_hasils')
            ->join('penyewaans', 'penyewaans.id', '=', 'bagi_hasils.penyewaan_id')
            ->join('motors', 'motors.id', '=', 'penyewaans.motor_id')
            ->where('motors.pemilik_id', $pemilik->id)
            ->whereNotNull('bagi_hasils.settled_at')
            ->sum('bagi_hasils.nominal_pemilik');

        $pendapatanTertunda = (float) DB::table('bagi_hasils')
            ->join('penyewaans', 'penyewaans.id', '=', 'bagi_hasils.penyewaan_id')
            ->join('motors', 'motors.id', '=', 'penyewaans.motor_id')
            ->where('motors.pemilik_id', $pemilik->id)
            ->whereNull('bagi_hasils.settled_at')
            ->sum('bagi_hasils.nominal_pemilik');

        $motorIds = $pemilik->motors()->pluck('id');

        return [
            'jumlahMotor' => (int) $motorPerStatus->sum(),
            'motorTersedia' => (int) ($motorPerStatus[Motor::STATUS_TERSEDIA] ?? 0),
            'motorDisewa' => (int) ($motorPerStatus[Motor::STATUS_DISEWA] ?? 0),
            'motorPending' => (int) ($motorPerStatus[Motor::STATUS_PENDING] ?? 0),
            'pendapatan' => $pendapatan,
            'pendapatanTertunda' => $pendapatanTertunda,
            'jumlahPenyewaan' => Penyewaan::whereIn('motor_id', $motorIds)->count(),
            'jumlahBagiHasil' => BagiHasil::query()
                ->whereHas('penyewaan.motor', fn ($q) => $q->where('pemilik_id', $pemilik->id))
                ->count(),
            // Motor miliknya saja, tidak pernah motor pemilik lain.
            'motorSaya' => $pemilik->motors()
                ->with('tarifRental')
                ->latest()
                ->limit(5)
                ->get(),
            'penyewaanTerbaru' => Penyewaan::query()
                ->whereIn('motor_id', $motorIds)
                ->with(['penyewa', 'motor', 'transaksis'])
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'bagiHasilTerbaru' => BagiHasil::query()
                ->with(['penyewaan.motor'])
                ->whereHas('penyewaan.motor', fn ($q) => $q->where('pemilik_id', $pemilik->id))
                ->latest('tanggal')
                ->limit(5)
                ->get(),
            'grafikPendapatan' => $this->grafikBagiHasilPemilik($pemilik->id),
        ];
    }

    /**
     * Ringkasan untuk penyewa: HANYA penyewaan & pembayaran miliknya.
     *
     * @return array<string, mixed>
     */
    public function penyewa(User $penyewa): array
    {
        $penyewaanIds = $penyewa->penyewaans()->pluck('id');

        $totalPembayaran = (float) Transaksi::whereIn('penyewaan_id', $penyewaanIds)
            ->where('status', Transaksi::STATUS_DIBAYAR)
            ->sum('jumlah');

        $pembayaranPending = (float) Transaksi::whereIn('penyewaan_id', $penyewaanIds)
            ->where('status', Transaksi::STATUS_PENDING)
            ->sum('jumlah');

        return [
            // Motor tersedia = data publik (boleh dilihat siapa pun yang login).
            'motorTersedia' => Motor::tersedia()->whereHas('tarifRental')->count(),
            'motorTersediaList' => Motor::tersedia()
                ->whereHas('tarifRental')
                ->with(['pemilik', 'tarifRental'])
                ->latest()
                ->limit(3)
                ->get(),
            'penyewaanAktif' => $penyewa->penyewaans()->aktif()->count(),
            'menungguKonfirmasi' => $penyewa->penyewaans()
                ->where('status', Penyewaan::STATUS_PENDING)
                ->count(),
            'totalPenyewaan' => $penyewa->penyewaans()->count(),
            'totalPembayaran' => $totalPembayaran,
            'pembayaranPending' => $pembayaranPending,
            'riwayat' => $penyewa->penyewaans()
                ->with(['motor.pemilik', 'transaksis'])
                ->latest('tanggal_mulai')
                ->limit(5)
                ->get(),
            'pembayaranTerbaru' => Transaksi::whereIn('penyewaan_id', $penyewaanIds)
                ->with(['penyewaan.motor'])
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ];
    }

    // ======================= GRAFIK =======================

    /**
     * Kerangka 6 bulan terakhir (label singkat Bahasa Indonesia).
     *
     * @return list<array{kunci: string, label: string, nilai: float}>
     */
    private function bulanTerakhir(int $jumlah = 6): array
    {
        $bulan = [];
        $awal = now()->startOfMonth()->subMonths($jumlah - 1);

        for ($i = 0; $i < $jumlah; $i++) {
            $tanggal = $awal->copy()->addMonths($i);
            $bulan[] = [
                'kunci' => $tanggal->format('Y-m'),
                'label' => $tanggal->locale('id')->translatedFormat('M'),
                'nilai' => 0.0,
            ];
        }

        return $bulan;
    }

    /**
     * @param  list<array{kunci: string, label: string, nilai: float}>  $bulan
     * @return array{label: list<string>, nilai: list<float>}
     */
    private function jadikanGrafik(array $bulan): array
    {
        return [
            'label' => array_column($bulan, 'label'),
            'nilai' => array_column($bulan, 'nilai'),
        ];
    }

    /**
     * Pendapatan admin: transaksi lunas (dibayar / dikembalikan) per bulan.
     *
     * @return array{label: list<string>, nilai: list<float>}
     */
    private function grafikTransaksi(): array
    {
        $bulan = $this->bulanTerakhir();
        $awal = now()->startOfMonth()->subMonths(5)->toDateString();

        $rows = Transaksi::query()
            ->whereIn('status', [Transaksi::STATUS_DIBAYAR, Transaksi::STATUS_DIKEMBALIKAN])
            ->where('tanggal', '>=', $awal)
            ->get(['jumlah', 'tanggal']);

        foreach ($rows as $row) {
            $kunci = Carbon::parse($row->tanggal)->format('Y-m');

            foreach ($bulan as $i => $item) {
                if ($item['kunci'] === $kunci) {
                    $bulan[$i]['nilai'] += (float) $row->jumlah;
                }
            }
        }

        return $this->jadikanGrafik($bulan);
    }

    /**
     * Pendapatan pemilik: nominal_pemilik dari bagi hasil per bulan.
     *
     * @return array{label: list<string>, nilai: list<float>}
     */
    private function grafikBagiHasilPemilik(int $pemilikId): array
    {
        $bulan = $this->bulanTerakhir();
        $awal = now()->startOfMonth()->subMonths(5)->toDateString();

        $rows = BagiHasil::query()
            ->sudahDicairkan()
            ->where('tanggal', '>=', $awal)
            ->whereHas('penyewaan.motor', fn ($q) => $q->where('pemilik_id', $pemilikId))
            ->get(['nominal_pemilik', 'tanggal']);

        foreach ($rows as $row) {
            $kunci = Carbon::parse($row->tanggal)->format('Y-m');

            foreach ($bulan as $i => $item) {
                if ($item['kunci'] === $kunci) {
                    $bulan[$i]['nilai'] += (float) $row->nominal_pemilik;
                }
            }
        }

        return $this->jadikanGrafik($bulan);
    }
}
