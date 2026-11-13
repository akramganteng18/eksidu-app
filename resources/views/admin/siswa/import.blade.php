@extends('layouts.app')
@section('title', 'Impor Siswa CSV')
@section('content')
<div class="space-y-6">
    <x-page-header eyebrow="Data master" title="Impor siswa massal" subtitle="Unggah CSV untuk memvalidasi dan membuat beberapa akun siswa sekaligus.">
        <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary">Kembali ke data siswa</a>
    </x-page-header>

    <div class="card card-pad space-y-4">
        <div>
            <h2 class="card-title">Format file CSV</h2>
            <p class="mt-1 text-sm text-ink-2">Gunakan UTF-8 dengan kolom <code>name,email,nis_nip,kelas,no_telp,password</code>. Kolom password wajib untuk setiap siswa dan akan langsung di-hash; nilai aslinya tidak disimpan di sesi pratinjau.</p>
            <a class="link mt-2 inline-flex text-sm" href="{{ route('admin.siswa.import.template') }}">Unduh template CSV</a>
        </div>
        <form method="POST" action="{{ route('admin.siswa.import.preview') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div><label class="label" for="file">Pilih file CSV (maks. 2 MB, 500 siswa)</label><input class="input" id="file" name="file" type="file" accept=".csv,text/csv" required></div>
            <button class="btn btn-primary" type="submit">Validasi dan tampilkan pratinjau</button>
        </form>
        @if($errors->has('file'))<div class="alert tone-rose">{{ $errors->first('file') }}</div>@endif
        @if($errors->has('import'))<div class="alert tone-rose">{{ $errors->first('import') }}</div>@endif
        @if(session('import_errors'))
            <div class="alert tone-rose"><div><strong>CSV belum dapat diimpor:</strong><ul class="mt-2 list-inside list-disc">@foreach(session('import_errors') as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
        @endif
    </div>

    @if($preview)
        <div class="card card-pad space-y-4">
            <div><x-badge tone="green">Validasi berhasil</x-badge><h2 class="mt-2 text-lg font-extrabold">Pratinjau {{ count($preview['rows'] ?? []) }} akun siswa</h2><p class="mt-1 text-sm text-ink-2">Pastikan identitas benar. Password tidak ditampilkan karena sudah di-hash di sesi server.</p></div>
            <div class="table-wrap"><table class="tbl tbl-cards-mobile"><thead><tr><th>Nama</th><th>Email</th><th>NIS</th><th>Kelas</th></tr></thead><tbody>
                @foreach(($preview['rows'] ?? []) as $row)
                    <tr><td>{{ $row['name'] }}</td><td>{{ $row['email'] }}</td><td>{{ $row['nis_nip'] ?: '—' }}</td><td>{{ $row['kelas'] ?: '—' }}</td></tr>
                @endforeach
            </tbody></table></div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.siswa.import.commit') }}" data-confirm="Buat {{ count($preview['rows'] ?? []) }} akun siswa dari pratinjau ini?">
                    @csrf <input type="hidden" name="batch" value="{{ $preview['batch'] }}">
                    <button class="btn btn-primary" type="submit">Konfirmasi dan impor {{ count($preview['rows'] ?? []) }} siswa</button>
                </form>
                <form method="POST" action="{{ route('admin.siswa.import.cancel') }}">@csrf<button class="btn btn-secondary" type="submit">Batalkan pratinjau</button></form>
            </div>
        </div>
    @endif
    <div class="alert tone-amber"><strong>Keamanan:</strong> gunakan file CSV contoh, pastikan data benar, dan hapus file CSV yang berisi password awal setelah proses impor selesai. Minta siswa mengganti password awal setelah login pertama.</div>
</div>
@endsection
