<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_barang',
        'deskripsi',
        'harga',
        'rating_barang',
        'category_id',
        'mitra_id',
        'image_barang',
        'status_post'
    ];

    /**
     * Get the category that owns the barang.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the mitra that owns the barang.
     */
    public function mitra()
    {
        return $this->belongsTo(Mitra::class, 'mitra_id');
    }

    public function komentars()
    {
        return $this->hasMany(Komentar::class);
    }

    public  function  cart()
    {
        return $this->hasMany(Cart::class);
    }
}
