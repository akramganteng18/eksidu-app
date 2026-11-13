<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isSiswa();
    }

    public function rules(): array
    {
        return [
            'kategori_layanan' => ['required', 'in:rapor,tka,skls,ijazah,legalisir'],
            'kategori_dokumen' => ['required_if:kategori_layanan,legalisir', 'nullable', 'in:rapor,tka,skls,ijazah'],
            'bentuk_dokumen' => ['required', 'in:fisik,digital'],
            'archive_id' => ['required', 'integer', 'exists:student_document_archives,id'],
            'semester_akademik' => ['nullable', 'integer', 'between:1,2'],
            'periode_rapor' => ['nullable', 'in:tengah,akhir'],
            'keperluan' => ['required', 'string', 'max:1000'],
            'jumlah_lembar' => ['required_if:kategori_layanan,legalisir', 'nullable', 'integer', 'min:1', 'max:20'],
            'file_pendukung' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . config('eksidu.batas_berkas_kb', 2048)],
        ];
    }

    public function messages(): array
    {
        return [
            'archive_id.required' => 'Pilih arsip dokumen yang tersedia.',
            'kategori_dokumen.required_if' => 'Pilih kategori dokumen yang akan dilegalisir.',
            'jumlah_lembar.required_if' => 'Jumlah lembar wajib diisi untuk layanan Legalisir.',
            'file_pendukung.mimes' => 'Berkas pendukung harus berformat PDF, JPG, atau PNG.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('archive_id') || ! $this->user()) return;
            $archive = $this->user()->documentArchives()->with('document')->find($this->integer('archive_id'));
            if (! $archive) {
                $validator->errors()->add('archive_id', 'Arsip tidak ditemukan pada akun Anda.');
                return;
            }
            $category = $this->input('kategori_layanan') === 'legalisir' ? $this->input('kategori_dokumen') : $this->input('kategori_layanan');
            if ($archive->category !== $category) $validator->errors()->add('archive_id', 'Kategori arsip tidak sesuai dengan layanan.');
            $available = $this->input('bentuk_dokumen') === 'digital' ? $archive->digitalAvailable() : $archive->physicalAvailable();
            if (! $available) $validator->errors()->add('bentuk_dokumen', 'Bentuk dokumen tersebut tidak tersedia pada arsip Anda.');
            if ($archive->category === 'rapor' && ((int) $this->input('semester_akademik') !== (int) $archive->semester || $this->input('periode_rapor') !== $archive->report_period)) {
                $validator->errors()->add('archive_id', 'Semester dan periode tidak cocok dengan arsip Rapor.');
            }
        });
    }
}
