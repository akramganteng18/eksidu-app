<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalLoan extends Model
{
    protected $fillable = [
        'inventory_item_id', 'user_id', 'approved_by', 'handed_over_by',
        'returned_to', 'status', 'purpose', 'admin_note', 'approved_at',
        'borrowed_at', 'due_at', 'returned_at', 'return_condition',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'borrowed_at' => 'datetime',
        'due_at' => 'date',
        'returned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(PhysicalInventoryItem::class, 'inventory_item_id');
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function handoverOfficer()
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    public function returnOfficer()
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'borrowed' && $this->due_at && $this->due_at->lt(today());
    }

    public function statusLabel(): string
    {
        if ($this->isOverdue()) return 'Terlambat';
        return [
            'pending' => 'Menunggu persetujuan',
            'approved' => 'Disetujui — menunggu serah terima',
            'borrowed' => 'Sedang dipinjam',
            'returned' => 'Dikembalikan',
            'rejected' => 'Ditolak',
        ][$this->status] ?? ucfirst($this->status);
    }
}
