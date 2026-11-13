<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PanduanMedia;
use App\Models\Pengumuman;
use App\Models\Pengajuan;
use App\Models\StudentDocumentArchive;
use App\Models\StudentArchiveRequirement;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Beranda "/": arahkan tamu ke login dan pengguna masuk ke dashboard. */
    public function beranda(Request $request)
    {
        return redirect()->route($request->user() ? 'dashboard' : 'login');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return $user->isAdmin() ? $this->adminDashboard($user) : $this->siswaDashboard($user);
    }

    protected function adminDashboard(User $user)
    {
        // Satu query untuk seluruh hitungan per status.
        $perStatus = Pengajuan::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $hitung = fn (string $s) => (int) ($perStatus[$s] ?? 0);

        $total = (int) $perStatus->sum();
        $arsipAktif = StudentDocumentArchive::with('document')->get();
        $ringkas = [
            'total'    => $total,
            'pending'  => $hitung('pending'),
            'diproses' => $hitung('diproses'),
            'approved' => $hitung('approved'),
            'selesai'  => $hitung('selesai'),
            'rejected' => $hitung('rejected'),
            'siswa'    => User::where('role', 'siswa')->count(),
            'siswa_aktif' => User::where('role', 'siswa')->where('aktif', true)->count(),
            'total_arsip' => $arsipAktif->count(),
            'digital_tersedia' => $arsipAktif->filter(fn ($archive) => $archive->digitalAvailable())->count(),
            'fisik_tersedia' => $arsipAktif->filter(fn ($archive) => $archive->physicalAvailable())->count(),
            'arsip_belum_lengkap' => User::where('role', 'siswa')->whereDoesntHave('documentArchives')->count(),
        ];

        // 6 bulan terakhir (dihitung per bulan; netral terhadap jenis database).
        $bulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $awal = now()->startOfMonth()->subMonths($i);
            $bulanan[] = [
                'label' => $awal->translatedFormat('M'),
                'bulan' => $awal->translatedFormat('F Y'),
                'total' => Pengajuan::whereBetween('created_at', [$awal, $awal->copy()->endOfMonth()])->count(),
            ];
        }

        $perJenis = Pengajuan::query()
            ->selectRaw('jenis_layanan, kategori_layanan, count(*) as total')
            ->groupBy('jenis_layanan', 'kategori_layanan')
            ->get();

        $antrean = Pengajuan::with(['user'])
            ->whereIn('status', ['pending', 'diproses'])
            ->oldest()
            ->limit(5)
            ->get();

        $pengajuanTerbaru = Pengajuan::with(['user', 'documentType', 'archive'])->latest()->limit(8)->get();
        $recentActivities = ActivityLog::with('user')->latest('created_at')->limit(8)->get();
        $jumlahPanduan = PanduanMedia::count();
        $pengumuman = Pengumuman::tayangUntuk($user)->latest('mulai_pada')->latest()->limit(5)->get();

        return view('dashboard.admin', compact(
            'ringkas', 'bulanan', 'perJenis', 'antrean', 'pengajuanTerbaru', 'recentActivities', 'jumlahPanduan', 'pengumuman'
        ));
    }

    protected function siswaDashboard(User $user)
    {
        $pengajuans = $user->pengajuans()->with(['documentType', 'archive', 'documentVersion'])->latest()->get();

        $ringkasan = [
            'total'    => $pengajuans->count(),
            'pending'  => $pengajuans->where('status', 'pending')->count(),
            'diproses' => $pengajuans->where('status', 'diproses')->count(),
            'siap'     => $pengajuans->where('status', 'approved')->count(),
            'selesai'  => $pengajuans->where('status', 'selesai')->count(),
            'ditolak'  => $pengajuans->where('status', 'rejected')->count(),
        ];

        $terbaru = $pengajuans->take(5);
        $siapDiambil = $pengajuans->firstWhere('status', 'approved');
        $dokumenDigitalAktif = $pengajuans->filter(fn ($p) => $p->bentuk_dokumen === 'digital' && $p->status === 'approved' && $p->digital_access_revoked_at === null && $p->documentVersion && $p->documentVersion->status === 'available' && $p->documentVersion->privateDisk()->exists($p->documentVersion->file_path))->count();
        $aktivitasSaya = ActivityLog::where('user_id', $user->id)->latest('created_at')->limit(5)->get();
        $jumlahPanduan = PanduanMedia::untukPengguna($user)->count();
        $pengumuman = Pengumuman::tayangUntuk($user)->latest('mulai_pada')->latest()->limit(5)->get();

        return view('dashboard.siswa', compact('ringkasan', 'terbaru', 'siapDiambil', 'dokumenDigitalAktif', 'aktivitasSaya', 'jumlahPanduan', 'pengumuman'));
    }
}
