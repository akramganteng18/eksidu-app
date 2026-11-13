<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentDocumentArchive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'identity_key', 'document_type_id', 'document_id', 'category', 'academic_year', 'semester',
        'report_period', 'publication_year', 'digital_status', 'physical_status', 'physical_location',
        'notes', 'version',
    ];

    protected $casts = ['semester' => 'integer', 'publication_year' => 'integer', 'version' => 'integer'];

    public const CATEGORIES = ['rapor' => 'Rapor', 'tka' => 'TKA', 'skls' => 'SKLS', 'ijazah' => 'Ijazah'];
    public const DIGITAL = ['belum_diunggah' => 'Belum Diunggah', 'tersedia' => 'Tersedia', 'tidak_tersedia' => 'Tidak Tersedia', 'diarsipkan' => 'Diarsipkan'];
    public const PHYSICAL = ['belum_tercatat' => 'Belum Tercatat', 'tersedia' => 'Tersedia', 'tidak_tersedia' => 'Tidak Tersedia', 'hilang' => 'Hilang', 'rusak' => 'Rusak'];

    public function student() { return $this->belongsTo(User::class, 'user_id'); }
    public function documentType() { return $this->belongsTo(DocumentType::class); }
    public function document() { return $this->belongsTo(Document::class); }
    public function histories() { return $this->hasMany(StudentDocumentArchiveHistory::class, 'archive_id')->latest(); }

    public function digitalAvailable(): bool
    {
        return $this->digital_status === 'tersedia' && $this->document && $this->document->status === 'available' && $this->document->privateDisk()->exists($this->document->file_path);
    }

    public function physicalAvailable(): bool { return $this->physical_status === 'tersedia'; }

    public function labelDetail(): string
    {
        if ($this->category !== 'rapor') {
            $label = self::CATEGORIES[$this->category] ?? $this->category;
            return $label . ($this->publication_year ? ' · ' . $this->publication_year : '');
        }
        return trim(($this->academic_year ?: 'Tahun ajaran belum diisi') . ' · Semester ' . ($this->semester ?: '-') . ' · ' . ($this->report_period === 'tengah' ? 'Tengah Semester' : ($this->report_period === 'akhir' ? 'Akhir Semester' : 'Periode belum diisi')));
    }
}
