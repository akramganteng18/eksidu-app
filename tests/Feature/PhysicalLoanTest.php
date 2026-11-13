<?php

namespace Tests\Feature;

use App\Models\PhysicalInventoryItem;
use App\Models\PhysicalLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalLoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_peminjaman_fisik_siswa_diarahkan_ke_ajukan_layanan(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('siswa.peminjaman-fisik.index'))
            ->assertRedirect(route('siswa.pengajuan.create'));
    }

    public function test_transaksi_peminjaman_fisik_lama_ditolak_tanpa_mengubah_stok_atau_histori(): void
    {
        $student = $this->buatSiswa();
        $admin = $this->buatAdmin();
        $item = PhysicalInventoryItem::create([
            'item_code' => 'RAPOR-LEGACY', 'name' => 'Rapor Fisik Lama', 'stock_total' => 2,
            'stock_available' => 2, 'active' => true,
        ]);
        $this->actingAs($student)->post(route('siswa.peminjaman-fisik.store', $item), ['purpose' => 'Keperluan administrasi'])
            ->assertRedirect(route('siswa.pengajuan.create'));
        $this->assertDatabaseCount('physical_loans', 0);
        $this->actingAs($admin)->post(route('admin.peminjaman-fisik.inventory.store'), [
            'item_code' => 'NEW', 'name' => 'Stok baru', 'stock_total' => 1,
        ])->assertRedirect(route('admin.arsip-siswa.index'));
        $this->assertDatabaseHas('physical_inventory_items', ['id' => $item->id, 'stock_available' => 2]);
    }

    public function test_histori_peminjaman_fisik_lama_tetap_dapat_dibaca_oleh_model(): void
    {
        $item = PhysicalInventoryItem::create([
            'item_code' => 'RAPOR-OLD', 'name' => 'Rapor Lama', 'stock_total' => 1,
            'stock_available' => 0, 'active' => true,
        ]);
        $loan = PhysicalLoan::create([
            'inventory_item_id' => $item->id, 'user_id' => $this->buatSiswa()->id,
            'purpose' => 'Riwayat transaksi lama', 'status' => 'borrowed',
            'borrowed_at' => now()->subDays(10), 'due_at' => today()->subDay(),
        ]);
        $this->assertTrue($loan->isOverdue());
        $this->assertSame('Terlambat', $loan->statusLabel());
    }

    public function test_siswa_tidak_dapat_membuka_halaman_admin_peminjaman_fisik(): void
    {
        $this->actingAs($this->buatSiswa())->get(route('admin.peminjaman-fisik.index'))->assertForbidden();
    }
}
