<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $kode_tiket
 * @property int $user_id
 * @property string $jenis_layanan
 * @property int|null $document_type_id
 * @property string $keperluan
 * @property string $status
 */
class Pengajuan extends Model
{
    use HasFactory;

    public const JENIS_LAYANAN = [
        'peminjaman_rapor'   => 'Peminjaman Rapor Fisik',
        'legalisir'          => 'Legalisir Ijazah / Rapor',
        'pengambilan_ijazah' => 'Pengambilan Ijazah Asli',
    ];

    public const JENIS_SINGKAT = [
        'peminjaman_rapor'   => 'Peminjaman Rapor',
        'legalisir'          => 'Legalisir',
        'pengambilan_ijazah' => 'Pengambilan Ijazah',
    ];

    // Satu sumber kebenaran untuk label status di seluruh aplikasi.
    public const STATUS_LABEL = [
        'pending'  => 'Menunggu',
        'diproses' => 'Diproses',
        'approved' => 'Siap Diambil',
        'selesai'  => 'Selesai',
        'rejected' => 'Ditolak',
    ];

    /**
     * Peta transisi status yang diperbolehkan (state machine).
     * Key = status saat ini, Value = daftar status tujuan yang valid.
     */
    public const ALLOWED_TRANSITIONS = [
        'pending'  => ['diproses'],
        'diproses' => ['approved', 'rejected'],
        'approved' => ['selesai'],
        'rejected' => [],
        'selesai'  => [],
    ];

    protected $fillable = [
        'kode_tiket', 'user_id', 'jenis_layanan', 'kategori_layanan', 'bentuk_dokumen', 'archive_id', 'document_version_id', 'result_document_id', 'academic_year', 'semester_akademik', 'periode_rapor', 'document_type_id', 'keperluan', 'physical_verification_hash', 'physical_verification_code', 'physical_verification_expires_at', 'digital_access_hash', 'digital_access_token', 'digital_access_revoked_at',
        'tanggal_dibutuhkan', 'jumlah_lembar', 'file_pendukung', 'status',
        'catatan_admin', 'verified_by', 'verified_at',
        'tanggal_pengembalian', 'kondisi_pengembalian',
    ];

    // Nilai awal di sisi model, agar objek yang baru dibuat langsung berstatus
    // "pending" (sebelumnya null sampai di-refresh, sehingga notifikasi pertama salah).
    protected $attributes = [
        'status' => 'pending',
    ];

    // Laravel 10: gunakan properti $casts (bukan method casts()).
    protected $casts = [
        'tanggal_dibutuhkan'   => 'date',
        'tanggal_pengembalian' => 'date',
        'verified_at'          => 'datetime',
        'physical_verification_expires_at' => 'datetime',
        'digital_access_revoked_at' => 'datetime',
        'jumlah_lembar'        => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Pengajuan $pengajuan) {
            if (empty($pengajuan->kode_tiket)) {
                $pengajuan->kode_tiket = self::generateKodeTiket();
            }
        });
    }

    /**
     * Nomor tiket berurutan per tahun, mis. EKSIDU-2026-000001.
     * Aman dari duplikat/race condition berkat row locking (lockForUpdate).
     */
    public static function generateKodeTiket(): string
    {
        $year = now()->year;
        $prefix = config('eksidu.prefix_tiket', 'EKSIDU');

        return DB::transaction(function () use ($year, $prefix) {
            $counter = TicketCounter::lockForUpdate()->firstOrCreate(
                ['year' => $year],
                ['last_number' => 0]
            );

            $counter->increment('last_number');

            return sprintf('%s-%d-%06d', $prefix, $year, $counter->last_number);
        });
    }

    public function resultDocument()
    {
        return $this->belongsTo(Document::class, 'result_document_id');
    }

    public function documentVersion()
    {
        return $this->belongsTo(Document::class, 'document_version_id');
    }

    public function archive()
    {
        return $this->belongsTo(StudentDocumentArchive::class, 'archive_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function riwayats()
    {
        return $this->hasMany(PengajuanRiwayat::class)->orderBy('created_at')->orderBy('id');
    }

    public function labelJenisLayanan(): string
    {
        if ($this->kategori_layanan) {
            $category = $this->archive?->category ? (StudentDocumentArchive::CATEGORIES[$this->archive->category] ?? Str::headline($this->archive->category)) : (StudentDocumentArchive::CATEGORIES[$this->kategori_layanan] ?? Str::headline($this->kategori_layanan));
            return ($this->jenis_layanan === 'legalisir' ? 'Legalisir ' : 'Permintaan ') . $category . ($this->bentuk_dokumen ? ' (' . ucfirst($this->bentuk_dokumen) . ')' : '');
        }
        return self::JENIS_LAYANAN[$this->jenis_layanan] ?? Str::headline((string) $this->jenis_layanan);
    }

    public function bisaBerpindahKe(string $statusBaru): bool
    {
        return in_array($statusBaru, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    /** Daftar status tujuan yang valid dari status saat ini. */
    public function statusTujuanValid(): array
    {
        return self::ALLOWED_TRANSITIONS[$this->status] ?? [];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABEL[$this->status] ?? Str::headline((string) $this->status);
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'pending'  => 'amber',
            'diproses' => 'sky',
            'approved' => 'green',
            'selesai'  => 'teal',
            'rejected' => 'rose',
            default    => 'slate',
        };
    }

    /** Dipertahankan agar kompatibel dengan pemanggil lama. */
    public function statusColor(): string
    {
        return $this->statusTone();
    }

    public function punyaBerkas(): bool
    {
        return ! empty($this->file_pendukung);
    }
}
