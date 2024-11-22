<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Klase extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'subject_id',
        'section',
        'schedule'
    ];

    // public function subject()
    // {
    //     return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    // }
} 
