<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory; 

    protected $fillable = [
        'fname',
        'lname',
        'mname',
        'suffix',
        'bdate',
        'bplace',
        'gender',
        'religion',
        'address',
        'contact_no',
        'email',
        'password',
    ];
}
