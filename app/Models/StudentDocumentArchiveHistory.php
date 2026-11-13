<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocumentArchiveHistory extends Model
{
    protected $fillable = ['archive_id', 'actor_id', 'field', 'old_value', 'new_value', 'notes'];
    public function archive() { return $this->belongsTo(StudentDocumentArchive::class, 'archive_id'); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
