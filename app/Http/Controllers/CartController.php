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
        // Mengurai JSON ke array
        $request->merge([
            'barang_id' => json_decode($request->input('barang_id')),
            'quantity' => json_decode($request->input('quantity')),
        ]);

        // Validasi
        $request->validate([
            'barang_id' => 'required|array',
            'barang_id.*' => 'exists:barangs,id',
            'quantity' => 'required|array',
            'quantity.*' => 'integer|min:1',
        ]);

        $userId = Auth::id();
        $cartItems = [];

        foreach ($request->barang_id as $index => $barangId) {
            $quantity = $request->quantity[$index] ?? 1;

            $barang = Barang::find($barangId);
            if (!$barang) {
                return response()->json(['message' => 'Barang not found'], 404);
            }

            $totalPrice = $barang->harga * $quantity;

            $cartItem = Cart::updateOrCreate(
                [
                    'user_id' => $userId,
                    'barang_id' => $barangId,
                ],
                [
                    'quantity' => $quantity,
                    'total_price' => $totalPrice,
                ]
            );

            $cartItems[] = $cartItem->load('barang.mitra');
        }

        return response()->json(['message' => 'Products added to cart', 'cartItems' => $cartItems], 201);
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
