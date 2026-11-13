<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'no_telp' => ['nullable', 'string', 'max:20'],
            // Format eksplisit: tidak menerima SVG (berpotensi memuat skrip).
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            $user->hapusBerkasFoto();
            // Disimpan di disk privat; ditampilkan lewat AvatarController (tanpa storage:link).
            $validated['foto'] = $request->file('foto')->store('foto-profil', config('eksidu.disk_berkas'));
        } else {
            unset($validated['foto']);
        }

        $user->update($validated);
        ActivityLog::catat($user->id, 'update_profile', 'Memperbarui data profil.');

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::catat($request->user()->id, 'update_password', 'Mengubah kata sandi akun.');

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
