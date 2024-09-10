<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class   Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'barang_id',
        'user_id',
        'external_id',
        'no_transaction',
        'quantity',
        'invoice_url',
        'grand_total',
        'status',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
