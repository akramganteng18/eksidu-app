{{-- Dialog konfirmasi global. Dipicu oleh <form data-confirm="..."> (lihat resources/js/ui.js) --}}
<div x-data="confirmDialog" x-show="open" x-cloak x-transition.opacity.duration.150ms
     @keydown.escape.window="open && batal()" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="modal" @click.outside="batal()">
        <div class="flex items-start gap-4">
            <span class="chip h-11 w-11 shrink-0" :class="danger ? 'tone-rose' : 'tone-sky'">
                <span x-show="danger"><x-icon name="warning" size="h-6 w-6" /></span>
                <span x-show="!danger" x-cloak><x-icon name="info" size="h-6 w-6" /></span>
            </span>
            <div class="min-w-0">
                <h2 id="confirm-title" class="text-base font-bold" x-text="title"></h2>
                <p class="mt-1.5 text-sm text-ink-2" x-text="message"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" x-ref="batal" class="btn btn-secondary" @click="batal()">Batal</button>
            <button type="button" class="btn" :class="danger ? 'btn-danger' : 'btn-primary'" @click="lanjut()" x-text="action"></button>
        </div>
    </div>
</div>
