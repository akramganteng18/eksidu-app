@extends('layouts.app')
@section('title','Dokumen Saya')
@section('content')
<x-page-header eyebrow="Layanan saya" title="Dokumen Saya" subtitle="Dokumen digital yang telah disetujui dan aksesnya masih aktif." />
<div class="grid gap-4 md:grid-cols-2">@forelse($pengajuans as $p)<a href="{{ route('siswa.pengajuan.show',$p) }}" class="card card-pad hover:shadow-md"><p class="text-xs text-muted">{{ $p->kode_tiket }}</p><h2 class="font-bold mt-1">{{ $p->documentVersion?->title ?? $p->labelJenisLayanan() }}</h2><p class="text-sm text-muted">{{ $p->archive?->labelDetail() }}</p><span class="text-sm text-brand mt-3 inline-block">Buka detail dan QR →</span></a>@empty<div class="card card-pad text-muted">Belum ada dokumen digital yang dapat diakses.</div>@endforelse</div><div class="mt-5">{{ $pengajuans->links() }}</div>
@endsection
