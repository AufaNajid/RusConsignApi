<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $primaryKey = 'likeId'; // Primary key
    protected $fillable = ['user_id', 'barang_id']; // Fillable attributes

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Relasi ke model User
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id'); // Relasi ke model Barang
    }

    public function profile()
    {
        return $this->hasOne(ProfileImage::class, 'likeId', 'id');
    }

}
