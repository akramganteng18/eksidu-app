@extends('layouts.app')
@section('title', 'Peminjaman Dokumen Fisik')
@section('content')
<div class="space-y-6">
    <x-page-header eyebrow="Layanan dokumen fisik" title="Peminjaman dokumen fisik" subtitle="Pilih dokumen yang tersedia. Stok baru dialokasikan setelah permohonan disetujui petugas TU." />
    @if($errors->any())<div class="alert tone-rose"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(session('success'))<div class="alert tone-green">{{ session('success') }}</div>@endif
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($items as $item)
            <article class="card card-pad space-y-3">
                <div class="flex items-start justify-between gap-2"><div><p class="text-xs text-muted">{{ $item->item_code }}</p><h2 class="text-lg font-extrabold">{{ $item->name }}</h2></div><x-badge tone="green">{{ $item->stock_available }} tersedia</x-badge></div>
                @if($item->description)<p class="text-sm text-muted">{{ $item->description }}</p>@endif
                <p class="text-xs text-muted">Lokasi: {{ $item->location ?: 'Hubungi petugas TU' }}</p>
                <form method="POST" action="{{ route('siswa.peminjaman-fisik.store',$item) }}" class="space-y-2">@csrf<label class="label" for="purpose-{{ $item->id }}">Keperluan peminjaman</label><textarea class="input" id="purpose-{{ $item->id }}" name="purpose" rows="2" minlength="5" maxlength="1000" required placeholder="Jelaskan keperluan Anda"></textarea><button class="btn btn-primary w-full">Ajukan peminjaman</button></form>
            </article>
        @empty
            <div class="card card-pad md:col-span-2 xl:col-span-3"><x-empty-state art="docs" title="Belum ada dokumen tersedia" text="Silakan periksa kembali nanti atau hubungi petugas TU." /></div>
        @endforelse
    </div>
    <section class="space-y-3"><h2 class="text-lg font-extrabold">Riwayat peminjaman saya</h2><div class="card overflow-hidden"><div class="table-wrap"><table class="tbl tbl-cards-mobile"><thead><tr><th>Dokumen</th><th>Tanggal pinjam</th><th>Jatuh tempo</th><th>Dikembalikan</th><th>Status</th></tr></thead><tbody>
    @forelse($loans as $loan)<tr><td><strong>{{ $loan->item?->name }}</strong><p class="text-xs text-muted">{{ $loan->item?->item_code }}</p></td><td>{{ $loan->borrowed_at?->format('d/m/Y') ?? '—' }}</td><td>{{ $loan->due_at?->format('d/m/Y') ?? '—' }}</td><td>{{ $loan->returned_at?->format('d/m/Y') ?? '—' }}</td><td><x-badge :tone="$loan->isOverdue()?'rose':'amber'">{{ $loan->statusLabel() }}</x-badge></td></tr>
    @empty<tr><td colspan="5" class="text-center text-muted">Anda belum memiliki riwayat peminjaman fisik.</td></tr>@endforelse
    </tbody></table></div><div class="card-pad">{{ $loans->links() }}</div></div></section>
</div>
@endsection
