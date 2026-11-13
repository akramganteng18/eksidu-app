<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'document_type_id', 'uploaded_by', 'title', 'academic_year',
        'semester', 'graduation_year', 'disk', 'file_path', 'original_filename',
        'mime_type', 'file_size', 'checksum_sha256', 'status',
    ];

    protected $casts = [
        'semester' => 'integer',
        'graduation_year' => 'integer',
        'file_size' => 'integer',
    ];

    public function owner() { return $this->belongsTo(User::class, 'user_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function documentType() { return $this->belongsTo(DocumentType::class); }
    public function loans() { return $this->hasMany(DocumentLoan::class); }
    public function studentArchives() { return $this->hasMany(StudentDocumentArchive::class); }

    public function scopeAvailable($query) { return $query->where('status', 'available'); }

    public function privateDisk()
    {
        $disk = $this->disk ?: config('eksidu.disk_berkas', 'berkas');
        // Skema lama menggunakan label 'private'; Laravel filesystem proyek ini
        // mendaftarkan disk privat sebagai 'berkas'.
        if (in_array($disk, ['private', 'local'], true)) {
            $disk = config('eksidu.disk_berkas', 'berkas');
        }
        return Storage::disk($disk);
    }
}
