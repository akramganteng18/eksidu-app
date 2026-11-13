<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Akademik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'siswa')->withCount('pengajuans');

        if ($search = trim((string) $request->get('q'))) {
            $like = Akademik::likeAman($search);
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('nis_nip', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('kelas', 'like', $like);
            });
        }

        $siswa = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.siswa.index', compact('siswa'));
    }

    public function create()
    {
        return view('admin.siswa.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nis_nip' => ['nullable', 'string', 'max:50', 'unique:users,nis_nip'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'siswa';
        $validated['aktif'] = true;

        $siswa = User::create($validated);
        ActivityLog::catat(auth()->id(), 'tambah_siswa', "Menambahkan data siswa {$siswa->name}.");

        return redirect()->route('admin.siswa.index')->with('status', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(User $siswa)
    {
        abort_unless($siswa->role === 'siswa', 404);

        return view('admin.siswa.edit', compact('siswa'));
    }

    public function update(Request $request, User $siswa): RedirectResponse
    {
        abort_unless($siswa->role === 'siswa', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($siswa->id)],
            'nis_nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nis_nip')->ignore($siswa->id)],
            'kelas' => ['nullable', 'string', 'max:50'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'aktif' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', Password::defaults()],
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $siswa->update($validated);
        ActivityLog::catat(auth()->id(), 'ubah_siswa', "Memperbarui data siswa {$siswa->name}.");

        return redirect()->route('admin.siswa.index')->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(User $siswa): RedirectResponse
    {
        abort_unless($siswa->role === 'siswa', 404);

        // Relasi pengajuan memakai ON DELETE CASCADE: menghapus siswa akan ikut melenyapkan
        // seluruh riwayat layanannya. Dicegah; nonaktifkan akun bila siswa tidak lagi dipakai.
        if ($siswa->pengajuans()->exists()) {
            return back()->with('error',
                "{$siswa->name} masih memiliki riwayat pengajuan sehingga tidak dapat dihapus. "
                . 'Gunakan "Ubah" lalu nonaktifkan akunnya.');
        }

        $siswa->hapusBerkasFoto();

        $nama = $siswa->name;
        $siswa->delete();
        ActivityLog::catat(auth()->id(), 'hapus_siswa', "Menghapus data siswa {$nama}.");

        return back()->with('status', 'Data siswa berhasil dihapus.');
    }
}
