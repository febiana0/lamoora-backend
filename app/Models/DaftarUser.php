<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarUser extends Model
{
    protected $table = 'daftar_users';
    protected $primaryKey = 'username'; // <- tambah ini
    public $incrementing = false; // <- karena bukan angka
    protected $keyType = 'string'; // <- karena username itu string

    protected $fillable = [
        'username',
        'nama',
        'email',
        'password',
        'alamat',
        'role',
    ];

    protected $hidden = [
        'password',
    ];
}
