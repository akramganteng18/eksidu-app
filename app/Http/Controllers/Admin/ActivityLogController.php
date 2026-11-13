<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Akademik;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($search = trim((string) $request->get('q'))) {
            $like = Akademik::likeAman($search);
            $query->where(function ($q) use ($like) {
                $q->where('description', 'like', $like)
                  ->orWhere('action', 'like', $like)
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
            });
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($dari = $request->get('dari')) {
            $query->whereDate('created_at', '>=', $dari);
        }
        if ($sampai = $request->get('sampai')) {
            $query->whereDate('created_at', '<=', $sampai);
        }

        $logs = $query->latest('created_at')->latest('id')->paginate(20)->withQueryString();

        $daftarAksi = ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $daftarUser = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('admin.aktivitas.index', compact('logs', 'daftarAksi', 'daftarUser'));
    }
}
