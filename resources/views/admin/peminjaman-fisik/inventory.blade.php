@extends('layouts.app')
@section('title', 'Inventaris Dokumen Fisik')
@section('content')
<div class="space-y-6">
    <x-page-header eyebrow="Peminjaman fisik" title="Inventaris dokumen fisik" subtitle="Kelola identitas, lokasi, dan ketersediaan stok dokumen yang dapat dipinjam secara fisik." />
    <div class="card card-pad">
        <h2 class="mb-4 text-base font-extrabold">Tambah inventaris</h2>
        <form method="POST" action="{{ route('admin.peminjaman-fisik.inventory.store') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @csrf
            <div><label class="label" for="item_code">Kode inventaris</label><input class="input" id="item_code" name="item_code" required maxlength="40" value="{{ old('item_code') }}" placeholder="RAPOR-2026-001"></div>
            <div><label class="label" for="name">Nama dokumen</label><input class="input" id="name" name="name" required maxlength="255" value="{{ old('name') }}" placeholder="Rapor kelas XII"></div>
            <div><label class="label" for="stock_total">Jumlah stok</label><input class="input" id="stock_total" name="stock_total" type="number" min="1" max="100000" required value="{{ old('stock_total', 1) }}"></div>
            <div><label class="label" for="location">Lokasi penyimpanan</label><input class="input" id="location" name="location" maxlength="120" value="{{ old('location') }}"></div>
            <div class="md:col-span-2"><label class="label" for="description">Keterangan</label><input class="input" id="description" name="description" maxlength="1000" value="{{ old('description') }}"></div>
            <div><button class="btn btn-primary" type="submit">Tambah inventaris</button></div>
        </form>
    </div>
    @if($errors->any())<div class="alert tone-rose"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card overflow-hidden">
        <div class="table-wrap"><table class="tbl tbl-cards-mobile"><thead><tr><th>Identitas</th><th>Stok tersedia</th><th>Lokasi</th><th>Status</th><th>Ubah inventaris</th></tr></thead><tbody>
        @forelse($items as $item)
            <tr>
                <td><strong>{{ $item->name }}</strong><p class="text-xs text-muted">{{ $item->item_code }} · {{ $item->description }}</p></td>
                <td><strong>{{ $item->stock_available }} / {{ $item->stock_total }}</strong><p class="text-xs text-muted">{{ $item->active_loans_count }} dialokasikan/dipinjam</p></td>
                <td>{{ $item->location ?: '—' }}</td>
                <td><x-badge :tone="$item->active ? 'green' : 'slate'">{{ $item->active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                <td>
                    <form method="POST" action="{{ route('admin.peminjaman-fisik.inventory.update', $item) }}" class="grid gap-2 min-w-[230px]">
                        @csrf @method('PATCH')
                        <input class="input" name="item_code" value="{{ $item->item_code }}" required aria-label="Kode">
                        <input class="input" name="name" value="{{ $item->name }}" required aria-label="Nama">
                        <input class="input" name="stock_total" type="number" min="1" value="{{ $item->stock_total }}" required aria-label="Stok total">
                        <input class="input" name="location" value="{{ $item->location }}" aria-label="Lokasi">
                        <input class="input" name="description" value="{{ $item->description }}" aria-label="Keterangan">
                        <button class="btn btn-secondary btn-sm">Simpan perubahan</button>
                    </form>
                    <form method="POST" action="{{ route('admin.peminjaman-fisik.inventory.toggle', $item) }}" class="mt-2">@csrf @method('PATCH')<button class="btn btn-ghost btn-sm">{{ $item->active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                </td>
            </tr>
        @empty<tr><td colspan="5"><x-empty-state art="docs" title="Inventaris masih kosong" text="Tambahkan dokumen fisik yang dapat dipinjam." /></td></tr>@endforelse
        </tbody></table></div><div class="card-pad">{{ $items->links() }}</div>
    </div>
</div>
@endsection
