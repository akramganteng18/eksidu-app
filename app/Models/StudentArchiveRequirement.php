<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentArchiveRequirement extends Model
{
    protected $fillable = ['class_name', 'academic_year', 'category', 'semester', 'report_period', 'required', 'active', 'notes'];
    protected $casts = ['required' => 'boolean', 'active' => 'boolean', 'semester' => 'integer'];
}
