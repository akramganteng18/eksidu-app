@if ($errors->any())
    <div class="alert tone-rose mb-6" role="alert">
        <x-icon name="warning" size="mt-0.5 h-5 w-5" />
        <div class="min-w-0">
            <p class="font-bold">Periksa kembali isian Anda</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                @foreach (collect($errors->all())->unique()->take(6) as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
