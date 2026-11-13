<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLoan extends Model
{
    protected $fillable = [
        'document_id', 'user_id', 'approved_by', 'purpose', 'status', 'admin_note',
        'token_hash', 'qr_svg', 'expires_at', 'revoked_at', 'accessed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'accessed_at' => 'datetime',
    ];

    protected $hidden = ['token_hash'];

    public function document() { return $this->belongsTo(Document::class); }
    public function borrower() { return $this->belongsTo(User::class, 'user_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    public function tokenAktif(): bool
    {
        return $this->status === 'approved'
            && filled($this->token_hash)
            && is_null($this->revoked_at)
            && $this->expires_at
            && $this->expires_at->isFuture();
    }
}
