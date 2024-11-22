<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $primaryKey = "grade_id";

    protected $fillable = [  
        'admin_id',
        'LRN',
        'class_id',
        'grade',
        'term',
        'semester',
        'permission',
        'created_at',
        'updated_at'
    ];

}
