<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MitraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama_lengkap,
            'nis' => $this->nis,
            'nomor' => $this->no_dompet_digital,
            'image' => $this->image_id_card,
            'status' => $this->status,
            'pengikut'=>$this->pengikut,
            'jumlahproduct'=>$this->jumlah_product,
            'jumlahjasa'=>$this->jumlah_jasa,
            'penilaian'=>$this->penilaian,
        ];
    }
}
