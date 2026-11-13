<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentArchiveRequirementExemption extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id', 'requirement_id', 'created_by', 'reason'];
    public function requirement() { return $this->belongsTo(StudentArchiveRequirement::class, 'requirement_id'); }
    public function student() { return $this->belongsTo(User::class, 'user_id'); }
}
