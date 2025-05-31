<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'is_active',
        'role_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        //
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isKasir()
    {
        return $this->role_id === 2;
    }

    public function isAdmin()
    {
        return $this->role_id === 1;
    }
    public function isUser(){
        return $this->role_id === 3;
    }
}
