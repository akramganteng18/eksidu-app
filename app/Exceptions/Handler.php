<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // Tautan QR bertanda tangan yang kedaluwarsa/rusak: pesan ramah, bukan "Invalid signature".
        $this->renderable(function (InvalidSignatureException $e, $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return response()->view('errors.403', [
                'exception' => $e,
                'pesan' => 'Tautan atau QR Code ini tidak valid atau sudah kedaluwarsa. '
                    . 'Minta siswa membuka ulang halaman pengajuannya untuk menampilkan QR yang baru.',
            ], 403);
        });
    }
}
