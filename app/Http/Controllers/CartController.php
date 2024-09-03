<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            // Hitung rating rata-rata
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

            // Tambahkan nilai rating rata-rata ke dalam barang
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
            'barang_id' => 'required|exists:barangs,id',
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = Cart::where('user_id', Auth::id())->where('carts_id', $id)->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $barang = Barang::find($cartItem->barang_id);
        $totalPrice = $barang->harga * $request->quantity;

        $cartItem->quantity = $request->quantity;
        $cartItem->total_price = $totalPrice;
        $cartItem->save();

        $cartItem->load('barang.mitra');

        return response()->json(['message' => 'Cart item updated', 'cartItem' => $cartItem], 200);
    }

    public function destroy($id)
    {
        $cartItem = Cart::where('user_id', Auth::id())->where('carts_id', $id)->first();
        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Cart item removed'], 200);
    }
}
