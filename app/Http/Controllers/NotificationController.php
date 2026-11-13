<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        $pengajuanId = $notification->data['pengajuan_id'] ?? null;
        $pengajuan = $pengajuanId ? Pengajuan::find($pengajuanId) : null;

        if ($pengajuan && $user->can('view', $pengajuan)) {
            return redirect()->route($user->isAdmin() ? 'admin.pengajuan.show' : 'siswa.pengajuan.show', $pengajuan);
        }

        return redirect()->route('notifications.index');
    }
}
