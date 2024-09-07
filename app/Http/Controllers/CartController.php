<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Cart;
use App\Models\Cod;
use App\Models\Komentar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

    public function selectCartItems(Request $request)
    {
        $cartIds = $request->query('cart_ids');

        // Mengubah parameter menjadi array dari string
        $cartIdsArray = explode(',', $cartIds);

        // Validasi input
        $validated = Validator::make(['cart_ids' => $cartIdsArray], [
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'integer|exists:carts,carts_id',
        ])->validate();

        $selectedCartItems = Cart::where('user_id', Auth::id())
            ->whereIn('carts_id', $validated['cart_ids'])
            ->with('barang.mitra')
            ->get();

        if ($selectedCartItems->isEmpty()) {
            return response()->json(['message' => 'No selected cart items found'], 404);
        }

        return response()->json([
            'message' => 'Selected cart items found',
            'selected_cart_items' => $selectedCartItems,
        ], 200);
    }




    public function checkoutSelectedItems(Request $request)
    {
        $validated = $request->validate([
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'integer|exists:carts,id',
        ]);

        $selectedCartItems = Cart::where('user_id', Auth::id())
            ->whereIn('id', $validated['cart_ids'])
            ->with('barang.mitra')
            ->get();

        if ($selectedCartItems->isEmpty()) {
            return response()->json(['message' => 'No selected cart items found'], 404);
        }

        foreach ($selectedCartItems as $cartItem) {
            $barang = $cartItem->barang;

            if ($barang->stock_barang < $cartItem->quantity) {
                return response()->json(['message' => 'Insufficient stock for item: ' . $barang->nama_barang], 400);
            }

            $barang->stock_barang -= $cartItem->quantity;
            $barang->save();

        }

        // Hapus barang dari cart setelah checkout
        Cart::whereIn('id', $validated['cart_ids'])
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['message' => 'Checkout successful'], 200);
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

    public function destroySelected(Request $request)
    {
        $validated = $request->validate([
            'barang_id' => 'required|array',
            'barang_id.*' => 'integer|exists:carts,barang_id',
        ]);

        $user_id = Auth::id();

        $deleted = Cart::where('user_id', $user_id)
            ->whereIn('barang_id', $validated['barang_id'])
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'No cart items found for the selected barang_id'], 404);
        }

        return response()->json(['message' => 'Selected cart items removed'], 200);
    }

}
