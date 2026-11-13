<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PhysicalInventoryItem;
use Illuminate\Http\Request;

/** Legacy physical loan endpoint: new requests are centralized in Ajukan Layanan. */
class PhysicalLoanController extends Controller
{
    public function index()
    {
        return redirect()->route('siswa.pengajuan.create')->with('status', 'Permintaan dokumen fisik kini dilakukan melalui Ajukan Layanan.');
    }

    public function store(Request $request, PhysicalInventoryItem $item)
    {
        return redirect()->route('siswa.pengajuan.create')->with('error', 'Peminjaman fisik lama dinonaktifkan untuk transaksi baru. Gunakan Ajukan Layanan.');
    }
}
