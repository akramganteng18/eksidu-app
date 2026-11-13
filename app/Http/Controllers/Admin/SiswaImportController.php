<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SiswaImportController extends Controller
{
    public function create(Request $request)
    {
        return view('admin.siswa.import', ['preview' => $request->session()->get('siswa_import_preview')]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('siswa_import_preview');
        return redirect()->route('admin.siswa.import.create')->with('status', 'Pratinjau impor dibatalkan.');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            // BOM agar header UTF-8 terbaca baik di Excel.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['name', 'email', 'nis_nip', 'kelas', 'no_telp', 'password']);
            fputcsv($out, ['Nama Siswa Contoh', 'siswa@example.sch.id', '1234567890', 'XII RPL 1', '081234567890', 'GantiPassword123']);
            fclose($out);
        }, 'template-impor-siswa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        // Pratinjau sebelumnya dibuang saat unggahan baru dimulai.
        $request->session()->forget('siswa_import_preview');
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => 'File CSV tidak dapat dibaca.']);
        }

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            if (! is_array($headers)) {
                return back()->withErrors(['file' => 'File CSV kosong.']);
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $headers = array_map(fn ($value) => strtolower(trim((string) $value)), $headers);
            $required = ['name', 'email', 'nis_nip', 'kelas', 'no_telp', 'password'];
            if (array_diff($required, $headers) || count($headers) !== count(array_unique($headers))) {
                return back()->withErrors(['file' => 'Header wajib dan tidak boleh duplikat: name,email,nis_nip,kelas,no_telp,password. Unduh template untuk format yang benar.']);
            }

            $rows = [];
            $errors = [];
            $seenEmails = [];
            $seenNis = [];
            $line = 1;

            while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $line++;
                if ($values === [null] || count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) continue;
                if (count($rows) >= 500) {
                    $errors[] = "Maksimal 500 siswa per file (baris {$line} dan seterusnya tidak diproses).";
                    break;
                }
                if (count($values) !== count($headers)) {
                    $errors[] = "Baris {$line}: jumlah kolom tidak sesuai header.";
                    continue;
                }

                $raw = array_combine($headers, array_map(fn ($value) => trim((string) $value), $values));
                foreach (['nis_nip', 'kelas', 'no_telp'] as $optional) {
                    if (($raw[$optional] ?? '') === '') $raw[$optional] = null;
                }
                $validator = Validator::make($raw, [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'nis_nip' => ['nullable', 'string', 'max:50', 'unique:users,nis_nip'],
                    'kelas' => ['nullable', 'string', 'max:50'],
                    'no_telp' => ['nullable', 'string', 'max:20'],
                    'password' => ['required', 'string', Password::defaults()],
                ]);
                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $message) $errors[] = "Baris {$line}: {$message}";
                    continue;
                }

                $emailKey = mb_strtolower($raw['email']);
                $nisKey = trim((string) $raw['nis_nip']);
                if (isset($seenEmails[$emailKey])) $errors[] = "Baris {$line}: email duplikat di dalam file.";
                if ($nisKey !== '' && isset($seenNis[$nisKey])) $errors[] = "Baris {$line}: NIS duplikat di dalam file.";
                $seenEmails[$emailKey] = true;
                if ($nisKey !== '') $seenNis[$nisKey] = true;

                $rows[] = [
                    'name' => $raw['name'],
                    'email' => $raw['email'],
                    'nis_nip' => $nisKey ?: null,
                    'kelas' => $raw['kelas'] ?: null,
                    'no_telp' => $raw['no_telp'] ?: null,
                    // Password asli tidak disimpan dalam sesi pratinjau.
                    'password' => Hash::make($raw['password']),
                ];
            }

            if ($errors) return back()->with('import_errors', $errors);
            if (! $rows) return back()->withErrors(['file' => 'Tidak ada baris siswa valid untuk diimpor.']);

            $batch = Str::random(40);
            $request->session()->put('siswa_import_preview', ['batch' => $batch, 'rows' => $rows]);

            return redirect()->route('admin.siswa.import.create')->with('status', count($rows) . ' baris CSV lolos validasi. Periksa pratinjau sebelum menyimpan.');
        } finally {
            fclose($handle);
        }
    }

    public function commit(Request $request): RedirectResponse
    {
        $data = $request->validate(['batch' => ['required', 'string', 'size:40']]);
        $preview = $request->session()->get('siswa_import_preview');
        abort_unless(is_array($preview) && hash_equals($preview['batch'] ?? '', $data['batch']), 419, 'Pratinjau impor sudah kedaluwarsa. Unggah ulang file CSV.');
        $rows = $preview['rows'] ?? [];
        abort_if(count($rows) < 1 || count($rows) > 500, 422, 'Jumlah data impor tidak valid.');

        try {
            DB::transaction(function () use ($rows, $request) {
                $emails = array_column($rows, 'email');
                $nis = array_values(array_filter(array_column($rows, 'nis_nip')));
                $duplicate = User::whereIn('email', $emails)->orWhereIn('nis_nip', $nis)->exists();
                if ($duplicate) {
                    abort(422, 'Sebagian email atau NIS sudah digunakan. Unggah ulang CSV yang sudah diperbaiki.');
                }
                foreach ($rows as $row) {
                    User::create($row + ['role' => 'siswa', 'aktif' => true]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('admin.siswa.import.create')->withErrors(['import' => 'Impor dibatalkan. Periksa kembali duplikasi data atau log aplikasi.']);
        }

        $request->session()->forget('siswa_import_preview');
        ActivityLog::catat($request->user()->id, 'impor_siswa_csv', 'Mengimpor ' . count($rows) . ' akun siswa melalui CSV.');

        return redirect()->route('admin.siswa.index')->with('status', count($rows) . ' akun siswa berhasil diimpor.');
    }
}
