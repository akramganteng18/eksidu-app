<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhysicalLoan;
use Illuminate\Http\Request;

/** Legacy loan history is retained in storage; new transactions use Pengajuan. */
class PhysicalLoanController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.pengajuan.index')->with('status', 'Transaksi fisik baru kini diproses melalui Pengajuan Masuk.');
    }

    public function approve(Request $request, PhysicalLoan $loan)
    {
        return redirect()->route('admin.pengajuan.index')->with('error', 'Alur peminjaman fisik lama tidak menerima transaksi baru. Gunakan Pengajuan Masuk.');
    }

    public function reject(Request $request, PhysicalLoan $loan)
    {
        return redirect()->route('admin.pengajuan.index')->with('error', 'Alur peminjaman fisik lama tidak menerima transaksi baru. Gunakan Pengajuan Masuk.');
    }

    public function handover(Request $request, PhysicalLoan $loan)
    {
        return redirect()->route('admin.pengajuan.index')->with('error', 'Alur peminjaman fisik lama tidak menerima transaksi baru. Gunakan Pengajuan Masuk.');
    }

    public function returnItem(Request $request, PhysicalLoan $loan)
    {
        return redirect()->route('admin.pengajuan.index')->with('error', 'Alur peminjaman fisik lama tidak menerima transaksi baru. Gunakan Pengajuan Masuk.');
    }
}
