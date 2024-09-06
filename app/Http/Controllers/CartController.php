<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Cart;
use App\Models\Komentar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{

    public function index()
    {
        $cartItems = Cart::where('user_id', Auth::id())
            ->whereHas('barang')
            ->with(['barang.mitra'])
            ->get();

        $cartItems->map(function ($cartItem) {
            $barang = $cartItem->barang;

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

            $cartItem->barang->rating_barang = $avg;
        });

        return response()->json([
            "message" => "Data Cart berhasil ditemukan",
            "cart" => $cartItems,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'barang_id' => 'required|exists:barangs,id|array',
            'quantity' => 'required|integer|min:1',
        ]);

        $barang = Barang::find($request->barang_id);
        $totalPrice = $barang->harga * $request->quantity;

        $cartItem = Cart::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'barang_id' => $request->barang_id,
            ],
            [
                'quantity' => $request->quantity,
                'total_price' => $totalPrice,
            ]
        );

        $cartItem->load('barang.mitra');

        return response()->json(['message' => 'Product added to cart', 'cartItem' => $cartItem], 201);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => 'required|array',
            'cart_id.*' => 'integer|exists:carts,id',
        ]);

        $cartItems = Cart::where('user_id', Auth::id())
            ->whereIn('id', $validated['cart_ids'])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'No cart items found'], 404);
        }

        // Menghapus semua item cart yang ditemukan
        Cart::whereIn('id', $validated['cart_ids'])
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['message' => 'Cart items removed'], 200);
    }

}
