@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
@php $belumDibaca = auth()->user()->unreadNotifications()->count(); @endphp
<x-page-header eyebrow="Akun" title="Notifikasi" subtitle="Pemberitahuan perubahan status pengajuan Anda.">
    @if ($belumDibaca > 0)
        <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
            <button type="submit" class="btn btn-secondary"><x-icon name="check" size="h-4 w-4" /> Tandai semua dibaca ({{ $belumDibaca }})</button>
        </form>
    @endif
</x-page-header>

<div class="card overflow-hidden">
    @if ($notifications->isEmpty())
        <x-empty-state art="inbox" title="Belum ada notifikasi" text="Anda akan mendapat pemberitahuan di sini setiap kali status pengajuan berubah." />
    @else
        <ul class="divide-line">
            @foreach ($notifications as $n)
                @php $data = $n->data; $tone = match ($data['status'] ?? '') { 'pending' => 'amber', 'diproses' => 'sky', 'approved' => 'green', 'selesai' => 'teal', 'rejected' => 'rose', default => 'slate' }; @endphp
                <li class="tone-{{ $tone }} relative flex gap-4 px-5 py-4 sm:px-6 {{ $n->read_at ? '' : 'bg-brand-soft/40' }}">
                    <span class="chip mt-0.5 h-10 w-10 shrink-0"><x-icon :name="match ($data['status'] ?? '') { 'approved' => 'check-circle', 'rejected' => 'x-circle', 'selesai' => 'stamp', 'diproses' => 'refresh', default => 'clock' }" size="h-5 w-5" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('notifications.open', $n->id) }}" class="font-mono text-xs font-bold text-brand hover:underline">{{ $data['kode_tiket'] ?? 'Pemberitahuan' }}</a>
                            @isset($data['status_label'])<x-badge :tone="$tone">{{ $data['status_label'] }}</x-badge>@endisset
                            @unless ($n->read_at)<span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase" style="background: rgb(var(--c-brand)); color: rgb(var(--c-brand-fg))">Baru</span>@endunless
                        </div>
                        <p class="mt-1 text-sm leading-relaxed text-ink-2">{{ $data['message'] ?? '' }}</p>
                        <p class="mt-1.5 text-xs text-muted">{{ $n->created_at->diffForHumans() }} · {{ $n->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    <div class="flex shrink-0 items-start gap-1">
                        <a href="{{ route('notifications.open', $n->id) }}" class="btn btn-secondary btn-sm">Buka</a>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="card-pad pt-2">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
