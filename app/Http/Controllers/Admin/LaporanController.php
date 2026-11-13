<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengajuan;
use App\Support\XlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    /** Batas baris untuk halaman cetak agar peramban tidak kehabisan memori. */
    private const BATAS_CETAK = 1000;

    protected function filteredQuery(Request $request)
    {
        $query = Pengajuan::with(['user', 'documentType', 'archive']);

        if ($dari = $request->get('dari')) {
            $query->whereDate('created_at', '>=', $dari);
        }
        if ($sampai = $request->get('sampai')) {
            $query->whereDate('created_at', '<=', $sampai);
        }
        if (($status = $request->get('status')) && isset(Pengajuan::STATUS_LABEL[$status])) {
            $query->where('status', $status);
        }
        if (($jenis = $request->get('jenis_layanan')) && isset(Pengajuan::JENIS_LAYANAN[$jenis])) {
            $query->where('jenis_layanan', $jenis);
        }
        if (($kategori = $request->get('kategori_layanan')) && in_array($kategori, ['rapor', 'tka', 'skls', 'ijazah'], true)) $query->where('kategori_layanan', $kategori);
        if (($bentuk = $request->get('bentuk_dokumen')) && in_array($bentuk, ['fisik', 'digital'], true)) $query->where('bentuk_dokumen', $bentuk);
        if ($tahun = $request->get('academic_year')) $query->where('academic_year', $tahun);
        if (($semester = $request->get('semester_akademik')) && in_array((int) $semester, [1, 2], true)) $query->where('semester_akademik', (int) $semester);
        if (($periode = $request->get('periode_rapor')) && in_array($periode, ['tengah', 'akhir'], true)) $query->where('periode_rapor', $periode);

        return $query->latest();
    }

    public function index(Request $request)
    {
        $pengajuans = $this->filteredQuery($request)->paginate(15)->withQueryString();

        // Ringkasan sesuai filter yang sedang dipakai.
        $perStatus = $this->filteredQuery($request)->reorder()
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.laporan.index', compact('pengajuans', 'perStatus'));
    }

    /** Halaman siap-cetak; simpan sebagai PDF lewat dialog cetak peramban. */
    public function cetak(Request $request)
    {
        $pengajuans = $this->filteredQuery($request)->limit(self::BATAS_CETAK)->get();

        ActivityLog::catat(auth()->id(), 'cetak_laporan', 'Membuka laporan pengajuan siap cetak/PDF.');

        return view('admin.laporan.cetak', [
            'pengajuans' => $pengajuans,
            'dicetakOleh' => auth()->user(),
            'tanggalCetak' => now(),
            'terpotong' => $pengajuans->count() >= self::BATAS_CETAK,
            'filter' => $request->only(['dari', 'sampai', 'status', 'jenis_layanan', 'kategori_layanan', 'bentuk_dokumen', 'academic_year', 'semester_akademik', 'periode_rapor']),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $header = ['Kode Tiket', 'Nama Siswa', 'NIS', 'Kelas', 'Jenis Layanan', 'Jenis Dokumen',
                   'Tahun Ajaran', 'Semester', 'Periode Rapor', 'Bentuk Dokumen', 'Tanggal Pengajuan', 'Status', 'Catatan Admin'];

        $baris = $this->filteredQuery($request)->get()->map(fn (Pengajuan $p) => [
            $p->kode_tiket,
            $p->user->name ?? '-',
            $p->user->nis_nip ?? '-',
            $p->user->kelas ?? '-',
            $p->labelJenisLayanan(),
            $p->documentType->nama ?? '-',
            $p->academic_year ?? '-',
            $p->semester_akademik ? 'Semester ' . $p->semester_akademik : '-',
            $p->periode_rapor ?? '-',
            $p->bentuk_dokumen ?? '-',
            $p->created_at->format('d-m-Y H:i'),
            $p->statusLabel(),
            $p->catatan_admin ?? '-',
        ])->all();

        ActivityLog::catat(auth()->id(), 'export_laporan', 'Mengekspor laporan pengajuan ke Excel.');

        $nama = 'laporan-pengajuan-eksidu-' . now()->format('Ymd-His');

        if (XlsxWriter::tersedia()) {
            $tmp = XlsxWriter::buat($header, $baris, 'Pengajuan');

            return response()->download($tmp, $nama . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // Cadangan bila ext-zip tidak aktif: CSV (UTF-8 BOM, pemisah titik koma untuk Excel lokal Indonesia).
        return new StreamedResponse(function () use ($header, $baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';');
            foreach ($baris as $r) {
                // Awali dengan apostrof bila sel berpotensi dibaca sebagai rumus.
                fputcsv($out, array_map(
                    fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : $v,
                    $r
                ), ';');
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nama . '.csv"',
        ]);
    }
}
