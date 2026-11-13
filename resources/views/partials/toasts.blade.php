@php
    $toasts = [];
    if (session('status')) $toasts[] = ['tone' => 'green', 'title' => 'Berhasil', 'message' => session('status')];
    if (session('error')) $toasts[] = ['tone' => 'rose', 'title' => 'Tidak dapat diproses', 'message' => session('error')];
    if (session('warning')) $toasts[] = ['tone' => 'amber', 'title' => 'Perhatian', 'message' => session('warning')];
@endphp
{{-- Toast: notifikasi singkat setelah operasi berhasil/gagal (pesan flash dari server) --}}
<div id="toast-root" x-data="toasts" data-toasts="{{ json_encode($toasts, JSON_UNESCAPED_UNICODE) }}"
     class="pointer-events-none fixed inset-x-0 bottom-0 z-[70] flex flex-col items-end gap-2 p-4 sm:bottom-auto sm:left-auto sm:right-4 sm:top-4 sm:w-[25rem]"
     role="status" aria-live="polite">
    <template x-for="t in items" :key="t.id">
        <div class="toast" :class="'tone-' + t.tone">
            <span class="chip">
                <span x-show="t.tone === 'green'"><x-icon name="check-circle" size="h-5 w-5" /></span>
                <span x-show="t.tone === 'rose'" x-cloak><x-icon name="x-circle" size="h-5 w-5" /></span>
                <span x-show="t.tone === 'amber'" x-cloak><x-icon name="warning" size="h-5 w-5" /></span>
                <span x-show="t.tone === 'sky'" x-cloak><x-icon name="info" size="h-5 w-5" /></span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-bold" x-text="t.title"></p>
                <p class="mt-0.5 text-ink-2" x-text="t.message"></p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon !h-8 !w-8" @click="remove(t.id)" aria-label="Tutup notifikasi"><x-icon name="x" size="h-4 w-4" /></button>
        </div>
    </template>
</div>
