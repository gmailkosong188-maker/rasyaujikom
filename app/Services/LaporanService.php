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
 * Query laporan (spesifikasi §24).
 *
 * SEMUA angka berasal dari database. Tidak ada nominal/persentase yang
 * di-hardcode di sini maupun di controller.
 *
 * Batas akses diberikan lewat parameter: laporan admin memakai seluruh data,
 * laporan pemilik dibatasi `motors.pemilik_id = $pemilik->id` dan laporan
 * penyewa dibatasi `penyewaans.penyewa_id = $penyewa->id`.
 */
class LaporanService
{
    /**
     * Rentang tanggal laporan. Default: bulan berjalan.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rentang(?string $dari, ?string $sampai): array
    {
        $dari = $dari ? Carbon::parse($dari)->startOfDay() : now()->startOfMonth();
        $sampai = $sampai ? Carbon::parse($sampai)->endOfDay() : now()->endOfDay();

        if ($dari->greaterThan($sampai)) {
            [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
        }

        return [$dari, $sampai];
    }

    /**
     * Total pendapatan sah = transaksi lunas (dibayar atau sudah dikembalikan).
     */
    private function totalPendapatan($query): float
    {
        return (float) $query->whereIn('status', [
            Transaksi::STATUS_DIBAYAR,
            Transaksi::STATUS_DIKEMBALIKAN,
        ])->sum('jumlah');
    }

    // ======================= ADMIN =======================

    /**
     * Laporan admin: seluruh sistem.
     *
     * @return array<string, mixed>
     */
    public function admin(Carbon $dari, Carbon $sampai): array
    {
        $motorPerStatus = Motor::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendapatan = $this->totalPendapatan(
            Transaksi::query()->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
        );

        // Hanya dana yang SUDAH dicairkan admin yang dihitung sebagai bagian diterima.
        $bagianPemilik = (float) BagiHasil::query()
            ->sudahDicairkan()
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->sum('nominal_pemilik');

        $bagianAdmin = (float) BagiHasil::query()
            ->sudahDicairkan()
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->sum('nominal_admin');

        // Dana yang masih menunggu pencairan (belum diserahkan ke pemilik).
        $menungguPencairan = (float) BagiHasil::query()
            ->menungguPencairan()
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->sum('nominal_pemilik');

        return [
            'jumlahMotor' => (int) $motorPerStatus->sum(),
            'motorDisewa' => (int) ($motorPerStatus[Motor::STATUS_DISEWA] ?? 0),
            'motorTersedia' => (int) ($motorPerStatus[Motor::STATUS_TERSEDIA] ?? 0),
            'motorPerStatus' => $motorPerStatus,
            'jumlahPenyewaan' => Penyewaan::query()
                ->whereBetween('tanggal_mulai', [$dari->toDateString(), $sampai->toDateString()])->count(),
            'totalPendapatan' => $pendapatan,
            'bagianPemilik' => $bagianPemilik,
            'bagianAdmin' => $bagianAdmin,
            'menungguPencairan' => $menungguPencairan,
            'penyewaanPerStatus' => Penyewaan::query()
                ->whereBetween('tanggal_mulai', [$dari->toDateString(), $sampai->toDateString()])
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'grafikHarian' => $this->grafikPenyewaan($dari, $sampai, 'hari'),
            'grafikMingguan' => $this->grafikPenyewaan($dari, $sampai, 'minggu'),
            'grafikBulanan' => $this->grafikPenyewaan($dari, $sampai, 'bulan'),
            'pembayaran' => Transaksi::query()
                ->with(['penyewaan.penyewa', 'penyewaan.motor'])
                ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
                ->latest('tanggal')
                ->get(),
            'bagiHasil' => BagiHasil::query()
                ->with(['penyewaan.motor.pemilik'])
                ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
                ->latest('tanggal')
                ->get(),
        ];
    }

    /**
     * Grafik jumlah penyewaan per periode (harian/mingguan/bulanan).
     *
     * @return array{label: list<string>, nilai: list<int>}
     */
    public function grafikPenyewaan(Carbon $dari, Carbon $sampai, string $satuan): array
    {
        $rows = Penyewaan::query()
            ->whereBetween('tanggal_mulai', [$dari->toDateString(), $sampai->toDateString()])
            ->select('tanggal_mulai')
            ->get();

        $hitung = [];

        foreach ($rows as $row) {
            $tanggal = $row->tanggal_mulai instanceof Carbon
                ? $row->tanggal_mulai->copy()
                : Carbon::parse($row->tanggal_mulai);

            $kunci = match ($satuan) {
                'minggu' => $tanggal->copy()->startOfWeek()->format('d/m'),
                'bulan' => $tanggal->copy()->startOfMonth()->format('M Y'),
                default => $tanggal->format('d/m'),
            };

            $hitung[$kunci] = ($hitung[$kunci] ?? 0) + 1;
        }

        return [
            'label' => array_keys($hitung),
            'nilai' => array_values($hitung),
        ];
    }

    // ======================= PEMILIK =======================

    /**
     * Laporan pemilik: HANYA motor miliknya (spesifikasi §24).
     *
     * @return array<string, mixed>
     */
    public function pemilik(User $pemilik, Carbon $dari, Carbon $sampai): array
    {
        $motorIds = $pemilik->motors()->pluck('id');

        // Pendapatan per motor untuk periode ini.
        $pendapatanPerMotor = DB::table('transaksis')
            ->join('penyewaans', 'penyewaans.id', '=', 'transaksis.penyewaan_id')
            ->join('motors', 'motors.id', '=', 'penyewaans.motor_id')
            ->where('motors.pemilik_id', $pemilik->id)
            ->whereIn('transaksis.status', [Transaksi::STATUS_DIBAYAR, Transaksi::STATUS_DIKEMBALIKAN])
            ->whereBetween('transaksis.tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->groupBy('motors.id', 'motors.merk', 'motors.no_plat')
            ->select(
                'motors.id',
                'motors.merk',
                'motors.no_plat',
                DB::raw('COUNT(transaksis.id) as jumlah_transaksi'),
                DB::raw('SUM(transaksis.jumlah) as total_pendapatan')
            )
            ->get();

        $bagiHasil = BagiHasil::query()
            ->with(['penyewaan.motor', 'pencairan'])
            ->whereHas('penyewaan.motor', fn ($q) => $q->where('pemilik_id', $pemilik->id))
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->latest('tanggal')
            ->get();

        return [
            'motorIds' => $motorIds,
            'jumlahMotor' => $motorIds->count(),
            'motorDisewakan' => Motor::query()
                ->whereIn('id', $motorIds)
                ->whereIn('status', [Motor::STATUS_DISEWA, Motor::STATUS_TERSEDIA])
                ->with('tarifRental')
                ->get(),
            'pendapatanPerMotor' => $pendapatanPerMotor,
            'totalPendapatan' => (float) $pendapatanPerMotor->sum('total_pendapatan'),
            'bagiHasil' => $bagiHasil,
            // Total yang benar-benar sudah diterima pemilik.
            'totalBagiHasil' => (float) $bagiHasil->whereNotNull('settled_at')->sum('nominal_pemilik'),
            // Total yang masih menunggu dicairkan admin.
            'totalMenungguPencairan' => (float) $bagiHasil->whereNull('settled_at')->sum('nominal_pemilik'),
            'jumlahPenyewaan' => Penyewaan::query()
                ->whereIn('motor_id', $motorIds)
                ->whereBetween('tanggal_mulai', [$dari->toDateString(), $sampai->toDateString()])
                ->count(),
        ];
    }

    // ======================= PENYEWA =======================

    /**
     * Laporan penyewa: HANYA penyewaan miliknya (spesifikasi §24).
     *
     * @return array<string, mixed>
     */
    public function penyewa(User $penyewa, Carbon $dari, Carbon $sampai): array
    {
        $penyewaan = Penyewaan::query()
            ->where('penyewa_id', $penyewa->id)
            ->whereBetween('tanggal_mulai', [$dari->toDateString(), $sampai->toDateString()])
            ->with(['motor.pemilik', 'transaksis', 'bagiHasil'])
            ->latest('tanggal_mulai')
            ->get();

        $pembayaran = Transaksi::query()
            ->whereHas('penyewaan', fn ($q) => $q->where('penyewa_id', $penyewa->id))
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->with('penyewaan.motor')
            ->latest('tanggal')
            ->get();

        return [
            'penyewaan' => $penyewaan,
            'pembayaran' => $pembayaran,
            'penyewaanAktif' => $penyewaan->whereIn('status', Penyewaan::STATUS_AKTIF)->count(),
            'penyewaanSelesai' => $penyewaan->where('status', Penyewaan::STATUS_SELESAI)->count(),
            'totalPembayaran' => $this->totalPendapatan(
                Transaksi::query()
                    ->whereHas('penyewaan', fn ($q) => $q->where('penyewa_id', $penyewa->id))
                    ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ),
        ];
    }
}
