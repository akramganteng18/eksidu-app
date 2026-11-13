@extends('layouts.app')
@section('title','Arsip Dokumen Siswa')
@section('content')
<x-page-header eyebrow="Layanan dokumen" title="Arsip Dokumen Siswa" subtitle="Kelola arsip akademik secara terpusat per akun siswa." />
<div class="card card-pad mb-5">
<h2 class="card-title mb-3">Standar kelengkapan arsip</h2>
<form method="POST" action="{{ route('admin.arsip-siswa.requirements.store') }}" class="grid gap-2 sm:grid-cols-3">@csrf
<input name="class_name" class="input" placeholder="Kelas (kosong = semua kelas)">
<input name="academic_year" class="input" placeholder="Tahun ajaran (opsional)">
<select name="category" class="input" required>@foreach(\App\Models\StudentDocumentArchive::CATEGORIES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
<select name="semester" class="input"><option value="">Semua semester</option><option value="1">Semester 1</option><option value="2">Semester 2</option></select>
<select name="report_period" class="input"><option value="">Semua periode</option><option value="tengah">Tengah Semester</option><option value="akhir">Akhir Semester</option></select>
<select name="required" class="input"><option value="1">Wajib</option><option value="0">Tidak wajib</option></select>
<input name="notes" class="input sm:col-span-2" placeholder="Catatan aturan (opsional)"><button class="btn btn-primary">Tambah Aturan</button></form>
<div class="mt-4 flex flex-wrap gap-2">@foreach($requirements as $rule)<form method="POST" action="{{ route('admin.arsip-siswa.requirements.delete',$rule) }}" class="chip">@csrf @method('DELETE')<span>{{ $rule->class_name ?: 'Semua kelas' }} · {{ \App\Models\StudentDocumentArchive::CATEGORIES[$rule->category] }} {{ $rule->semester ? 'S'.$rule->semester : '' }} {{ $rule->report_period ?: '' }} · {{ $rule->required ? 'Wajib' : 'Tidak wajib' }}</span><button class="ml-2 text-rose-600" title="Nonaktifkan">×</button></form>@endforeach</div>
</div>
<div class="card card-pad mb-5"><form method="GET" class="flex flex-wrap gap-3"><input name="q" value="{{ request('q') }}" class="input flex-1 min-w-48" placeholder="Cari nama atau NIS"><select name="kelas" class="input"><option value="">Semua kelas</option>@foreach($classes as $kelas)<option @selected(request('kelas')===$kelas)>{{ $kelas }}</option>@endforeach</select><button class="btn btn-primary">Cari</button></form></div>
<div class="card overflow-hidden"><div class="table-wrap"><table class="table w-full"><thead><tr><th>Siswa</th><th>NIS</th><th>Kelas</th><th>Arsip wajib</th><th>Jumlah arsip</th><th>Aksi</th></tr></thead><tbody>@forelse($students as $student)<tr><td class="font-semibold">{{ $student->name }}</td><td>{{ $student->nis_nip ?: '—' }}</td><td>{{ $student->kelas ?: '—' }}</td><td>{{ $student->archive_requirement_total ? $student->archive_requirement_available.'/'.$student->archive_requirement_total : 'Standar belum diatur' }} @if($student->archive_requirement_total)<span class="text-xs text-muted">· kurang {{ $student->archive_requirement_missing }} · tidak berlaku {{ $student->archive_requirement_exempt }}</span>@endif</td><td>{{ $student->document_archives_count }}</td><td><a class="btn btn-secondary btn-sm" href="{{ route('admin.arsip-siswa.show',$student) }}">Kelola Dokumen</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-8">Belum ada siswa ditemukan.</td></tr>@endforelse</tbody></table></div><div class="p-4">{{ $students->links() }}</div></div>
@endsection
