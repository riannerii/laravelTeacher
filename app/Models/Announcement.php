<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $primaryKey = 'ancmnt_id';
    protected $fillable =[
        'admin_id',
        'class_id',
        'title',
        'announcement',
        
    ];
}
