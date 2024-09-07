<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Komentar;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    public function index()
    {
        $likeItems = Like::where('user_id', Auth::id())
            ->whereHas('barang')
            ->with(['barang.mitra'])
            ->get();

        $likeItems->map(function ($likeItems) {
            $barang = $likeItems->barang;

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

            $likeItems->barang->rating_barang = $avg;
        });

        return response()->json([
            "message" => "Data Like berhasil ditemukan",
            "cart" => $likeItems,
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
