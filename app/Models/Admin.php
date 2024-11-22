<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'admins';
    protected $primaryKey = "admin_id";
    protected $fillable = [  
        'admin_id',
        'fname',
        'lname',
        'mname',
        'role',
        'address',
        'email',
        'admin_pic',
        'password'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
