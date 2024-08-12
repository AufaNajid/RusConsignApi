<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_barang' => $this->nama_barang,
            'deskripsi' => $this->deskripsi,
            'harga' => $this->harga,
            'rating_barang' => $this->rating_barang,
            'mitra_id' => $this->mitra_id,
            'status' => $this->status_post,
            'stock' => $this->stock_barang,
            'image_barang' => $this->image_barang,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
