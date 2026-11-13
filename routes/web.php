<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\DocumentRepositoryController;
use App\Http\Controllers\Admin\DocumentLoanController;
use App\Http\Controllers\Admin\PhysicalInventoryController;
use App\Http\Controllers\Admin\PhysicalLoanController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\SiswaImportController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PanduanMediaController;
use App\Http\Controllers\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PanduanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Siswa\PengajuanController as SiswaPengajuanController;
use App\Http\Controllers\Siswa\DocumentRepositoryController as SiswaDocumentRepositoryController;
use App\Http\Controllers\Siswa\PhysicalLoanController as SiswaPhysicalLoanController;
use App\Http\Controllers\Siswa\DigitalDocumentController;
use App\Http\Controllers\Admin\StudentDocumentArchiveController;
use Illuminate\Support\Facades\Route;

// Controller (bukan closure) agar `php artisan route:cache` dapat dipakai di produksi.
Route::get('/', [DashboardController::class, 'beranda'])->name('beranda');

// ----- Tamu (belum login) -----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:10,1')->name('two-factor.challenge.store');

    // Registrasi mandiri hanya hidup bila EKSIDU_REGISTRASI_PUBLIK=true (lihat config/eksidu.php).
    if (config('eksidu.registrasi_publik')) {
        Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('/register', [RegisteredUserController::class, 'store']);
    }
});

// QR pengajuan: halaman ringkasan read-only dapat dibuka dari perangkat pemindai
// tanpa login. Tanda tangan membatasi URL dan masa berlaku; aksi konfirmasi tetap
// hanya tersedia pada route Admin yang memerlukan auth + role:admin.
Route::get('/pengajuan/qr/{pengajuan}', [AdminPengajuanController::class, 'qrVerify'])
    ->middleware(['signed:relative', 'throttle:30,1'])->name('pengajuan.qr.show');

// Foto profil: auth + akun aktif, TANPA 'nocache' agar gambar boleh di-cache peramban pemiliknya.
Route::middleware(['auth', 'active'])->get('/avatar/{user}', [AvatarController::class, 'show'])->name('avatar.show');

// ----- Terautentikasi (semua role). Halaman privat tidak boleh di-cache browser. -----
Route::middleware(['auth', 'active', 'nocache'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/{id}/buka', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifikasi/{id}/baca', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // QR repository: dapat dibuka oleh peminjam yang login atau Admin/TU.
    Route::get('/repository/qr/{loan}/{token}', [DocumentLoanController::class, 'access'])
        ->middleware(['signed:relative', 'throttle:30,1'])->name('repository.qr.access');
    Route::get('/repository/peminjaman/{loan}/preview', [DocumentLoanController::class, 'preview'])
        ->middleware('throttle:30,1')->name('repository.preview');

    // Berkas pendukung: izin diperiksa di PengajuanPolicy (admin / pemilik).
    Route::get('/berkas/pengajuan/{pengajuan}', [BerkasController::class, 'pengajuan'])->name('berkas.pengajuan');
    Route::get('/dokumen-saya', [DigitalDocumentController::class, 'index'])->middleware('role:siswa')->name('siswa.dokumen-saya.index');
    Route::get('/dokumen-saya/{pengajuan}/{token}', [DigitalDocumentController::class, 'show'])->middleware(['signed:relative', 'throttle:30,1'])->name('siswa.dokumen.digital');
    Route::get('/dokumen-saya/{pengajuan}/{token}/file', [DigitalDocumentController::class, 'file'])->middleware(['signed:relative', 'throttle:30,1'])->name('siswa.dokumen.digital.file');

    // Pusat Panduan (video/audio): izin diperiksa di PanduanMediaPolicy.
    Route::get('/panduan', [PanduanController::class, 'index'])->name('panduan.index');
    Route::get('/panduan/{panduan}', [PanduanController::class, 'show'])->name('panduan.show');
    Route::get('/panduan/{panduan}/media', [PanduanController::class, 'stream'])->name('panduan.stream');

    // ----- Siswa / Alumni -----
    Route::middleware('role:siswa')->prefix('peminjaman-fisik')->name('siswa.peminjaman-fisik.')->group(function () {
        Route::get('/', [SiswaPhysicalLoanController::class, 'index'])->name('index');
        Route::post('/{item}', [SiswaPhysicalLoanController::class, 'store'])->name('store');
    });

    Route::middleware('role:siswa')->prefix('repository')->name('siswa.repository.')->group(function () {
        Route::get('/', [SiswaDocumentRepositoryController::class, 'index'])->name('index');
        Route::post('/{document}/pinjam', [SiswaDocumentRepositoryController::class, 'requestLoan'])->name('request');
        Route::get('/peminjaman/{loan}/unduh', [SiswaDocumentRepositoryController::class, 'download'])->middleware('throttle:30,1')->name('download');
    });

    Route::middleware('role:siswa')->prefix('pengajuan')->name('siswa.pengajuan.')->group(function () {
        Route::get('/', [SiswaPengajuanController::class, 'index'])->name('index');
        Route::get('/buat', [SiswaPengajuanController::class, 'create'])->name('create');
        Route::post('/', [SiswaPengajuanController::class, 'store'])->name('store');
        Route::get('/lacak/cari', [SiswaPengajuanController::class, 'lacakForm'])->name('lacak.form');
        Route::post('/lacak/cari', [SiswaPengajuanController::class, 'lacakCari'])->name('lacak.cari');
        Route::get('/{pengajuan}', [SiswaPengajuanController::class, 'show'])->name('show');
    });

    // ----- Admin / Petugas TU -----
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('peminjaman-fisik/inventaris', [PhysicalInventoryController::class, 'index'])->name('peminjaman-fisik.inventory');
        Route::post('peminjaman-fisik/inventaris', [PhysicalInventoryController::class, 'store'])->name('peminjaman-fisik.inventory.store');
        Route::patch('peminjaman-fisik/inventaris/{item}', [PhysicalInventoryController::class, 'update'])->name('peminjaman-fisik.inventory.update');
        Route::patch('peminjaman-fisik/inventaris/{item}/status', [PhysicalInventoryController::class, 'toggle'])->name('peminjaman-fisik.inventory.toggle');
        Route::get('peminjaman-fisik', [PhysicalLoanController::class, 'index'])->name('peminjaman-fisik.index');
        Route::post('peminjaman-fisik/{loan}/setujui', [PhysicalLoanController::class, 'approve'])->name('peminjaman-fisik.approve');
        Route::post('peminjaman-fisik/{loan}/tolak', [PhysicalLoanController::class, 'reject'])->name('peminjaman-fisik.reject');
        Route::post('peminjaman-fisik/{loan}/serah-terima', [PhysicalLoanController::class, 'handover'])->name('peminjaman-fisik.handover');
        Route::post('peminjaman-fisik/{loan}/pengembalian', [PhysicalLoanController::class, 'returnItem'])->name('peminjaman-fisik.return');

        Route::get('repository', [DocumentRepositoryController::class, 'index'])->name('repository.index');
        Route::post('repository', [DocumentRepositoryController::class, 'store'])->name('repository.store');
        Route::get('repository/{document}/edit', [DocumentRepositoryController::class, 'edit'])->name('repository.edit');
        Route::patch('repository/{document}', [DocumentRepositoryController::class, 'update'])->name('repository.update');
        Route::get('repository/{document}/unduh', [DocumentRepositoryController::class, 'download'])->middleware('throttle:30,1')->name('repository.download');
        Route::patch('repository/{document}/arsip', [DocumentRepositoryController::class, 'toggleArchive'])->name('repository.archive');
        Route::delete('repository/{document}', [DocumentRepositoryController::class, 'destroy'])->name('repository.destroy');
        Route::get('repository/peminjaman', [DocumentLoanController::class, 'index'])->name('repository.loans');
        Route::patch('repository/peminjaman/{loan}', [DocumentLoanController::class, 'update'])->name('repository.loans.update');
        Route::get('repository/qr/{loan}/{token}', [DocumentLoanController::class, 'verify'])->middleware(['signed:relative', 'throttle:30,1'])->name('repository.qr.verify');
        Route::post('repository/peminjaman/{loan}/selesai', [DocumentLoanController::class, 'complete'])->name('repository.loans.complete');
        Route::patch('repository/peminjaman/{loan}/cabut', [DocumentLoanController::class, 'revoke'])->name('repository.loans.revoke');

        Route::get('arsip-siswa', [StudentDocumentArchiveController::class, 'index'])->name('arsip-siswa.index');
        // Register the fixed requirements endpoint before the {siswa} parameter route.
        // Otherwise POST /arsip-siswa/persyaratan is consumed as /arsip-siswa/{siswa}.
        Route::post('arsip-siswa/persyaratan', [StudentDocumentArchiveController::class, 'storeRequirement'])->name('arsip-siswa.requirements.store');
        Route::get('arsip-siswa/{siswa}', [StudentDocumentArchiveController::class, 'show'])->name('arsip-siswa.show');
        Route::post('arsip-siswa/{siswa}', [StudentDocumentArchiveController::class, 'store'])->name('arsip-siswa.store');
        Route::post('arsip-siswa/{siswa}/persyaratan/{requirement}/tidak-berlaku', [StudentDocumentArchiveController::class, 'exemptRequirement'])->name('arsip-siswa.requirements.exempt');
        Route::delete('arsip-siswa/{siswa}/persyaratan/{requirement}/berlaku', [StudentDocumentArchiveController::class, 'restoreRequirement'])->name('arsip-siswa.requirements.restore');
        Route::delete('arsip-siswa/persyaratan/{requirement}', [StudentDocumentArchiveController::class, 'deleteRequirement'])->name('arsip-siswa.requirements.delete');
        Route::get('arsip-siswa/arsip/{archive}/file', [StudentDocumentArchiveController::class, 'download'])->name('arsip-siswa.download');
        Route::patch('arsip-siswa/arsip/{archive}', [StudentDocumentArchiveController::class, 'update'])->name('arsip-siswa.update');
        Route::delete('arsip-siswa/arsip/{archive}', [StudentDocumentArchiveController::class, 'destroy'])->name('arsip-siswa.destroy');
        Route::patch('arsip-siswa/arsip/{archive}/status', [StudentDocumentArchiveController::class, 'status'])->name('arsip-siswa.status');
        Route::post('pengajuan/{pengajuan}/hasil-legalisir', [AdminPengajuanController::class, 'uploadLegalizationResult'])->name('pengajuan.legalisir-result');
        Route::post('pengajuan/{pengajuan}/cabut-akses-digital', [AdminPengajuanController::class, 'revokeDigitalAccess'])->name('pengajuan.digital-revoke');
        Route::post('pengajuan/{pengajuan}/terbitkan-ulang-akses-digital', [AdminPengajuanController::class, 'reissueDigitalAccess'])->name('pengajuan.digital-reissue');
        Route::post('pengajuan/{pengajuan}/rotasi-kode-fisik', [AdminPengajuanController::class, 'rotatePhysicalCode'])->name('pengajuan.physical-code-rotate');

        Route::get('siswa/impor', [SiswaImportController::class, 'create'])->name('siswa.import.create');
        Route::get('siswa/impor/template', [SiswaImportController::class, 'template'])->name('siswa.import.template');
        Route::post('siswa/impor/pratinjau', [SiswaImportController::class, 'preview'])->name('siswa.import.preview');
        Route::post('siswa/impor/batal', [SiswaImportController::class, 'cancel'])->name('siswa.import.cancel');
        Route::post('siswa/impor/simpan', [SiswaImportController::class, 'commit'])->name('siswa.import.commit');
        Route::resource('siswa', SiswaController::class)->except(['show']);

        Route::resource('pengumuman', PengumumanController::class)->except(['show']);

        Route::get('keamanan/2fa', [TwoFactorController::class, 'show'])->name('security.two-factor');
        Route::post('keamanan/2fa/mulai', [TwoFactorController::class, 'begin'])->name('security.two-factor.begin');
        Route::post('keamanan/2fa/konfirmasi', [TwoFactorController::class, 'confirm'])->name('security.two-factor.confirm');
        Route::delete('keamanan/2fa', [TwoFactorController::class, 'disable'])->name('security.two-factor.disable');
        // Parameter harus sama dengan nama argumen DocumentType $dokumen pada controller.
        // Tanpa ini, pluralizer Laravel mengubah {dokumen} menjadi {dokuman} dan
        // implicit model binding tidak mengisi model yang diminta controller.
        Route::resource('dokumen', DocumentTypeController::class)
            ->parameters(['dokumen' => 'dokumen'])
            ->except(['show', 'create', 'edit']);
        Route::resource('panduan', PanduanMediaController::class)->except(['show']);

        Route::get('pengajuan', [AdminPengajuanController::class, 'index'])->name('pengajuan.index');
        Route::get('pengajuan/{pengajuan}', [AdminPengajuanController::class, 'show'])->name('pengajuan.show');
        Route::put('pengajuan/{pengajuan}/status', [AdminPengajuanController::class, 'updateStatus'])->name('pengajuan.status');
        Route::put('pengajuan/{pengajuan}/pengembalian', [AdminPengajuanController::class, 'catatPengembalian'])->name('pengajuan.pengembalian');

        Route::post('pengajuan/{pengajuan}/qr-konfirmasi', [AdminPengajuanController::class, 'qrKonfirmasi'])
            ->name('pengajuan.qrKonfirmasi');
        Route::get('scan-qr', [AdminPengajuanController::class, 'scanForm'])->name('scan-qr');
        Route::post('scan-qr/cari', [AdminPengajuanController::class, 'scanCari'])->middleware('throttle:10,1')->name('scan-qr.cari');

        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
        Route::get('laporan/export/excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
        Route::get('aktivitas', [ActivityLogController::class, 'index'])->name('aktivitas.index');
    });
});
