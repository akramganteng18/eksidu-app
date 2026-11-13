@extends('layouts.app')
@section('title', 'Repository Dokumen')
@section('content')
<div class="space-y-6">
    <x-page-header eyebrow="Manajemen arsip" title="Repository dokumen" subtitle="Pusat penyimpanan dokumen akademik dengan akses privat dan jejak aktivitas." />
    <div class="card card-pad">
        <h2 class="mb-4 text-base font-extrabold">Unggah dokumen</h2>
        <form method="POST" action="{{ route('admin.repository.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @csrf
            <div><label class="label" for="title">Judul dokumen</label><input class="input" id="title" name="title" value="{{ old('title') }}" required maxlength="255"></div>
            <div><label class="label" for="user_id">Pemilik (opsional)</label><select class="input" id="user_id" name="user_id"><option value="">Dokumen umum</option>@foreach(\App\Models\User::where('role','siswa')->orderBy('name')->get(['id','name','nis_nip']) as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->nis_nip }}</option>@endforeach</select></div>
            <div><label class="label" for="document_type_id">Jenis dokumen</label><select class="input" id="document_type_id" name="document_type_id"><option value="">Tidak ditentukan</option>@foreach($types as $type)<option value="{{ $type->id }}">{{ $type->nama }}</option>@endforeach</select></div>
            <div><label class="label" for="academic_year">Tahun ajaran</label><input class="input" id="academic_year" name="academic_year" placeholder="2026/2027" value="{{ old('academic_year') }}"></div>
            <div><label class="label" for="semester">Semester</label><select class="input" id="semester" name="semester"><option value="">-</option><option value="1">1</option><option value="2">2</option></select></div>
            <div><label class="label" for="graduation_year">Tahun lulus</label><input class="input" id="graduation_year" name="graduation_year" type="number" min="1990" max="2100"></div>
            <div class="md:col-span-2 xl:col-span-3"><label class="label" for="file">Berkas (PDF/JPG/PNG, maks. 20 MB)</label><input class="input" id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
            <div class="md:col-span-2 xl:col-span-3"><button class="btn btn-primary" type="submit"><x-icon name="upload" size="h-4 w-4"/> Simpan dokumen</button></div>
        </form>
        @if($errors->any())<div class="alert tone-rose mt-4"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </div>
    <form method="GET" class="card card-pad grid gap-3 md:grid-cols-4">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Cari judul, nama siswa, NIS...">
        <select class="input" name="status"><option value="">Semua status</option><option value="available" @selected(request('status')==='available')>Tersedia</option><option value="archived" @selected(request('status')==='archived')>Diarsipkan</option></select>
        <select class="input" name="type"><option value="">Semua jenis</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((string)request('type')===(string)$type->id)>{{ $type->nama }}</option>@endforeach</select>
        <button class="btn btn-secondary" type="submit">Terapkan filter</button>
    </form>
    <div class="card overflow-hidden">
        <div class="table-wrap"><table class="tbl tbl-cards-mobile"><thead><tr><th>Dokumen</th><th>Pemilik</th><th>Tahun</th><th>Status</th><th>Peminjaman</th><th>Aksi</th></tr></thead><tbody>
        @forelse($documents as $document)
            <tr><td><p class="font-semibold">{{ $document->title }}</p><p class="text-xs text-muted">{{ $document->original_filename }} · {{ number_format($document->file_size / 1024, 0) }} KB</p></td><td>{{ $document->owner?->name ?? 'Dokumen umum' }}</td><td>{{ $document->academic_year ?? '—' }}</td><td><x-badge :tone="$document->status==='available'?'green':'slate'">{{ $document->status==='available'?'Tersedia':'Diarsipkan' }}</x-badge></td><td>{{ $document->loans_count }}</td><td><div class="flex flex-wrap gap-2"><a class="btn btn-secondary btn-sm" href="{{ route('admin.repository.edit',$document) }}">Edit</a><a class="btn btn-secondary btn-sm" href="{{ route('admin.repository.download',$document) }}">Unduh</a><form method="POST" action="{{ route('admin.repository.archive',$document) }}">@csrf @method('PATCH')<button class="btn btn-secondary btn-sm">{{ $document->status==='available'?'Arsipkan':'Aktifkan' }}</button></form>@if(!$document->loans_count)<form method="POST" action="{{ route('admin.repository.destroy',$document) }}" data-confirm="Hapus dokumen ini secara permanen?">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm">Hapus</button></form>@endif</div></td></tr>
        @empty<tr><td colspan="6"><x-empty-state art="docs" title="Belum ada dokumen" text="Unggah dokumen pertama melalui formulir di atas." /></td></tr>@endforelse
        </tbody></table></div><div class="card-pad">{{ $documents->links() }}</div>
    </div>
</div>
@endsection
