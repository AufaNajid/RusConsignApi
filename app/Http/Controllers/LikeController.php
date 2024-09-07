<?php

namespace App\Http\Controllers;

use App\Models\Komentar;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $likes = Like::where('user_id', $user->id)
            ->with('barang.category:id,name', 'barang.mitra:id,nama_lengkap,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp, mitra.profileImage')
            ->get();

        if ($likes->isEmpty()) {
            return response()->json(['message' => 'No likes found'], 404);
        }

        $likeData = [];
        foreach ($likes as $like) {
            $barang = $like->barang;

            if (!$barang) {
                continue;
            }

            $rate = Komentar::select(
                DB::raw('count(1) as total'),
                'rate'
            )
                ->where('barang_id', $barang->id)
                ->groupBy('rate')
                ->get();

            $total = $rate->sum('total');
            $avg = $rate->reduce(function ($carry, $item) {
                    return $carry + ($item->total * $item->rate);
                }, 0) / ($total ?: 1);

            $likeData[] = [
                'id' => $like->likeid,
                'created_at' => $like->created_at,
                'updated_at' => $like->updated_at,
                'barang' => [
                    'id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'deskripsi' => $barang->deskripsi,
                    'harga' => $barang->harga,
                    'rating_barang' => $avg,
                    'category_id' => $barang->category->id,
                    'category_nama' => $barang->category->name,
                    'image_barang' => $barang->image_barang,
                    'status' => $barang->status_post,
                    'created_at' => $barang->created_at,
                    'updated_at' => $barang->updated_at,
                    'mitra' => [
                        'id' => $barang->mitra->id,
                        'nama_toko' => $barang->mitra->nama_toko,
                        'nama_lengkap' => $barang->mitra->nama_lengkap,
                        'jumlah_product' => $barang->mitra->jumlah_product,
                        'jumlah_jasa' => $barang->mitra->jumlah_jasa,
                        'pengikut' => $barang->mitra->pengikut,
                        'penilaian' => $barang->mitra->penilaian,
                        'no_whatsapp' => $barang->mitra->no_whatsapp,
//                        'profile_image' => $barang->mitra->profileImage->image_profile ?? null
                    ],
                ],
            ];
        }

        return response()->json([
            'message' => 'Likes data found successfully',
            'likes' => $likeData,
        ], 200);
    }




    public function favorite(Request $request)
    {
        $request->validate([
            'barang_id' => 'required|exists:barangs,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $like = Like::firstOrCreate([
            'user_id' => $user->id,
            'barang_id' => $request->barang_id,
        ]);

        $like->load('barang.mitra');

        return response()->json(['message' => 'Product liked', 'like' => $like], 200);
    }
    public function unfavorite($barang_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $like = Like::where('user_id', $user->id)
            ->where('barang_id', $barang_id)
            ->first();

        if (!$like) {
            return response()->json(['message' => 'Like not found'], 404);
        }

        $like->delete();

        return response()->json(['message' => 'Like removed'], 200);
    }
}
