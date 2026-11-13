<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhysicalInventoryItem;
use Illuminate\Http\Request;

/** Legacy inventory endpoints redirect to the per-student archive workflow. */
class PhysicalInventoryController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.arsip-siswa.index')->with('status', 'Inventaris dokumen fisik kini dikelola pada Arsip Dokumen Siswa.');
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('error', 'Perubahan inventaris fisik lama dinonaktifkan. Gunakan Arsip Dokumen Siswa.');
    }

    public function update(Request $request, PhysicalInventoryItem $item)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('error', 'Perubahan inventaris fisik lama dinonaktifkan. Gunakan Arsip Dokumen Siswa.');
    }

    public function toggle(Request $request, PhysicalInventoryItem $item)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('error', 'Perubahan inventaris fisik lama dinonaktifkan. Gunakan Arsip Dokumen Siswa.');
    }
}
