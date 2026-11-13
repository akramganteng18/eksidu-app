@extends('layouts.app')
@section('title', 'Transaksi Peminjaman Fisik')
@section('content')
<div class="space-y-6">
    <x-page-header eyebrow="Layanan fisik terpisah" title="Transaksi peminjaman fisik" subtitle="Setujui permohonan, catat serah terima, pantau keterlambatan, dan proses pengembalian." />
    <form method="GET" class="card card-pad grid gap-3 md:grid-cols-3">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Cari siswa, NIS, atau dokumen">
        <select class="input" name="status"><option value="">Semua status</option>@foreach(['pending'=>'Menunggu','approved'=>'Disetujui','borrowed'=>'Sedang dipinjam','returned'=>'Dikembalikan','rejected'=>'Ditolak'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select>
        <button class="btn btn-secondary">Terapkan filter</button>
    </form>
    @if($errors->any())<div class="alert tone-rose"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card overflow-hidden"><div class="table-wrap"><table class="tbl tbl-cards-mobile"><thead><tr><th>Peminjam / dokumen</th><th>Tanggal</th><th>Status</th><th>Catatan</th><th>Tindakan Admin</th></tr></thead><tbody>
    @forelse($loans as $loan)
        <tr>
            <td><strong>{{ $loan->borrower?->name }}</strong><p class="text-xs text-muted">{{ $loan->borrower?->nis_nip }} · {{ $loan->item?->name }} ({{ $loan->item?->item_code }})</p><p class="text-xs text-muted">{{ $loan->purpose }}</p></td>
            <td><p>Ajukan: {{ $loan->created_at->format('d/m/Y') }}</p><p>Pinjam: {{ $loan->borrowed_at?->format('d/m/Y H:i') ?? '—' }}</p><p>Jatuh tempo: {{ $loan->due_at?->format('d/m/Y') ?? '—' }}</p><p>Kembali: {{ $loan->returned_at?->format('d/m/Y H:i') ?? '—' }}</p></td>
            <td><x-badge :tone="$loan->isOverdue() ? 'rose' : (in_array($loan->status,['returned','approved']) ? 'green' : 'amber')">{{ $loan->statusLabel() }}</x-badge></td>
            <td>{{ $loan->admin_note ?: '—' }} @if($loan->return_condition)<p class="text-xs text-muted">Kondisi: {{ $loan->return_condition }}</p>@endif</td>
            <td><div class="min-w-[220px] space-y-2">
                @if($loan->status==='pending')
                    <form method="POST" action="{{ route('admin.peminjaman-fisik.approve',$loan) }}">@csrf<button class="btn btn-primary btn-sm w-full">Setujui & alokasikan stok</button></form>
                    <form method="POST" action="{{ route('admin.peminjaman-fisik.reject',$loan) }}" class="space-y-1">@csrf<input class="input" name="admin_note" required placeholder="Alasan penolakan"><button class="btn btn-secondary btn-sm w-full">Tolak</button></form>
                @elseif($loan->status==='approved')
                    <form method="POST" action="{{ route('admin.peminjaman-fisik.handover',$loan) }}" class="space-y-1">@csrf<label class="label">Batas pengembalian</label><input class="input" type="date" name="due_at" min="{{ today()->addDay()->toDateString() }}" value="{{ today()->addDays(7)->toDateString() }}" required><button class="btn btn-primary btn-sm w-full">Catat serah terima</button></form>
                @elseif($loan->status==='borrowed')
                    <form method="POST" action="{{ route('admin.peminjaman-fisik.return',$loan) }}" class="space-y-1">@csrf<label class="label">Kondisi saat kembali</label><select class="input" name="return_condition" required><option value="">Pilih kondisi</option><option>Baik</option><option>Rusak ringan</option><option>Rusak berat</option><option>Tidak lengkap</option></select><button class="btn btn-primary btn-sm w-full">Proses pengembalian</button></form>
                @else <span class="text-xs text-muted">Tidak ada tindakan lanjutan.</span> @endif
            </div></td>
        </tr>
    @empty<tr><td colspan="5"><x-empty-state art="docs" title="Belum ada transaksi" text="Permohonan siswa akan muncul di halaman ini." /></td></tr>@endforelse
    </tbody></table></div><div class="card-pad">{{ $loans->links() }}</div></div>
</div>
@endsection
