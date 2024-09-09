<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Cart;
use App\Models\Cod;
use App\Models\Komentar;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $carts = Cart::where('user_id', $user->id)
            ->with([
                'barang' => function ($query) {
                    $query->where('stock_barang', '>', 0);
                },
                'barang.category:id,name',
                'barang.mitra:id,nama_lengkap,nama_toko,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp',
                'barang.mitra.profileImage'
            ])
            ->get();

        if ($carts->isEmpty()) {
            return response()->json(['message' => 'No carts found'], 404);
        }

        $cartsData = [];
        foreach ($carts as $cart) {
            $barang = $cart->barang;

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

            // Hitung total harga (total price)
            $totalPrice = $barang->harga * $cart->quantity;

            $cartsData[] = [
                'cart_id' => $cart->carts_id,
                'quantity' => $cart->quantity,
                'total_price' => $totalPrice,
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
                    'stock' => $barang->stock_barang,
                    'quantity' => $barang->quantity,
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
                        'profile_image' => $barang->mitra->profileImage->image_profile ?? null
                    ],
                ],
            ];
        }

        return response()->json([
            'message' => 'Cart data retrieved successfully',
            'carts' => $cartsData,
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

    public function selectCartItems(Request $request)
    {
        $request->validate([
            'cart_ids' => 'required|string',
        ]);

        $cartIds = explode(',', $request->input('cart_ids'));

        $selectedCartItems = Cart::where('user_id', Auth::id())
            ->whereIn('carts_id', $cartIds)
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
            // Mengubah input JSON menjadi array
            $request->merge([
                'barang_id' => json_decode($request->input('barang_id')),
                'quantity' => json_decode($request->input('quantity')),
            ]);

            // Validasi input
            $request->validate([
                'barang_id' => 'required|array',
                'barang_id.*' => 'exists:barangs,id',
                'quantity' => 'required|array',
                'quantity.*' => 'integer|min:1',
            ]);

            $userId = Auth::id();
            $checkedOutItems = [];

            foreach ($request->barang_id as $index => $barangId) {
                $quantity = $request->quantity[$index] ?? 1;

                $barang = Barang::find($barangId);
                if (!$barang) {
                    return response()->json(['message' => 'Barang not found'], 404);
                }

                $totalPrice = $barang->harga * $quantity;

                // Simpan item checkout ke dalam cart atau update jika sudah ada
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

                $avgRating = $barang->ratings()->avg('rating') ?? 0;

                $checkedOutItems[] = [
                    'id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'deskripsi' => $barang->deskripsi,
                    'harga' => $barang->harga,
                    'rating_barang' => $avgRating,
                    'category_id' => $barang->category->id,
                    'category_nama' => $barang->category->name,
                    'image_barang' => $barang->image_barang,
                    'status' => $barang->status_post,
                    'stock' => $barang->stock_barang,
                    'status_post' => $barang->status_post,
                    'created_at' => $barang->created_at,
                    'updated_at' => $barang->updated_at,
                    'mitra' => [
                        'id' => $barang->mitra->id,
                        'nama_toko' => $barang->mitra->nama_toko ?? 'nama toko tidak tersedia',
                        'nama_lengkap' => $barang->mitra->nama_lengkap,
                        'jumlah_product' => $barang->mitra->jumlah_product,
                        'jumlah_jasa' => $barang->mitra->jumlah_jasa,
                        'pengikut' => $barang->mitra->pengikut,
                        'penilaian' => $barang->mitra->penilaian,
                        'no_whatsapp' => $barang->mitra->no_whatsapp,
                    'email' => $barang->mitra->email,
                    'profile_image' => $barang->mitra->profileImage->image_profile ?? null
                ],
            ];
        }

        return response()->json(['message' => 'Succesfully Checkout', 'checkedOutItems' => $checkedOutItems], 201);
    }




    public function destroy($id, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $cart = Cart::where('user_id', $user->id)
            ->where('carts_id', $id)
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cart->delete();
        return response()->json(['message' => 'Cart item removed'], 200);
    }

    public function destroyMultiple(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'cart_ids' => 'required|array|min:1',
            'cart_ids.*' => 'required|integer|exists:carts,carts_id',
        ]);

        $cartIds = $request->input('cart_ids');


        Cart::where('user_id', $user->id)
            ->whereIn('carts_id', $cartIds)
            ->delete();

        return response()->json(['message' => 'Selected cart items removed'], 200);
    }

}
