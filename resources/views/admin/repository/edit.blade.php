@extends('layouts.app')
@section('title', 'Edit Dokumen Repository')
@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <x-page-header eyebrow="Manajemen arsip" title="Edit dokumen repository" subtitle="Perbarui metadata atau ganti berkas. Berkas lama akan diganti setelah berkas baru berhasil disimpan." />
    <div class="card card-pad">
        <form method="POST" action="{{ route('admin.repository.update', $document) }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
            @csrf
            @method('PATCH')
            <div class="md:col-span-2">
                <label class="label" for="title">Judul dokumen</label>
                <input class="input" id="title" name="title" value="{{ old('title', $document->title) }}" required maxlength="255">
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="user_id">Pemilik</label>
                <select class="input" id="user_id" name="user_id">
                    <option value="">Dokumen umum</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected((string)old('user_id', $document->user_id)===(string)$student->id)>{{ $student->name }} — {{ $student->nis_nip }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="document_type_id">Jenis dokumen</label>
                <select class="input" id="document_type_id" name="document_type_id">
                    <option value="">Tidak ditentukan</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" @selected((string)old('document_type_id', $document->document_type_id)===(string)$type->id)>{{ $type->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="academic_year">Tahun ajaran</label>
                <input class="input" id="academic_year" name="academic_year" maxlength="20" placeholder="2026/2027" value="{{ old('academic_year', $document->academic_year) }}">
            </div>
            <div>
                <label class="label" for="semester">Semester</label>
                <select class="input" id="semester" name="semester">
                    <option value="">Tidak ditentukan</option>
                    <option value="1" @selected((string)old('semester', $document->semester)==='1')>1</option>
                    <option value="2" @selected((string)old('semester', $document->semester)==='2')>2</option>
                </select>
            </div>
            <div>
                <label class="label" for="graduation_year">Tahun lulus</label>
                <input class="input" id="graduation_year" name="graduation_year" type="number" min="1990" max="2100" value="{{ old('graduation_year', $document->graduation_year) }}">
            </div>
            <div>
                <label class="label" for="file">Ganti berkas (opsional)</label>
                <input class="input" id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png">
                <p class="mt-1 text-xs text-muted">Saat ini: {{ $document->original_filename }} · {{ number_format($document->file_size / 1024, 0) }} KB. Maksimal 20 MB.</p>
                @error('file')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2 flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit"><x-icon name="save" size="h-4 w-4"/> Simpan perubahan</button>
                <a class="btn btn-secondary" href="{{ route('admin.repository.index') }}">Batal</a>
            </div>
        </form>
        @if($errors->any())
            <div class="alert tone-rose mt-4"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
    </div>
</div>
@endsection
