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
            ->with('barang.category:id,name', 'barang.mitra:id,nama_lengkap,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp', 'barang.mitra.profileImage')
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

            // Hitung rating
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
                'total_price' => $totalPrice, // Tambahkan total harga di sini
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
            'message' => 'Cart data found successfully',
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
        // Validasi input - pastikan cart_ids adalah string yang dipisahkan oleh koma
        $request->validate([
            'cart_ids' => 'required|string',
        ]);

        // Mengubah string cart_ids menjadi array integer
        $cartIds = explode(',', $request->input('cart_ids'));

        // Ambil data dari cart berdasarkan IDs yang diberikan
        $selectedCartItems = Cart::where('user_id', Auth::id())
            ->whereIn('carts_id', $cartIds)
            ->with('barang.mitra')
            ->get();

        // Jika tidak ada data ditemukan
        if ($selectedCartItems->isEmpty()) {
            return response()->json(['message' => 'No selected cart items found'], 404);
        }

        // Kirim data yang ditemukan sebagai respons JSON
        return response()->json([
            'message' => 'Selected cart items found',
            'selected_cart_items' => $selectedCartItems,
        ], 200);
    }


    public function checkoutSelectedItems(Request $request)
    {
        // Validasi input - pastikan cart_ids adalah string yang dipisahkan oleh koma
        $request->validate([
            'cart_ids' => 'required|string',
        ]);

        // Mengubah string cart_ids menjadi array integer
        $cartIds = explode(',', $request->input('cart_ids'));

        // Ambil data dari cart berdasarkan IDs yang diberikan
        $selectedCartItems = Cart::where('user_id', Auth::id())
            ->whereIn('carts_id', $cartIds) // Ganti 'id' dengan 'carts_id'
            ->with('barang.mitra')
            ->get();

        // Jika tidak ada data ditemukan
        if ($selectedCartItems->isEmpty()) {
            return response()->json(['message' => 'No selected cart items found'], 404);
        }


        // Proses checkout
        foreach ($selectedCartItems as $cartItem) {
            $barang = $cartItem->barang;

            // Periksa apakah stok cukup
            if ($barang->stock_barang < $cartItem->quantity) {
                return response()->json(['message' => 'Insufficient stock for item: ' . $barang->nama_barang], 400);
            }

            // Kurangi stok barang
            $barang->stock_barang -= $cartItem->quantity;
            $barang->save();


            // Hapus barang dari cart setelah checkout
            Cart::whereIn('carts_id', $cartIds)
                ->where('user_id', Auth::id())
                ->delete();

            // Kirim respons JSON yang benar
            return response()->json([
                'message' => 'Checkout successful',
                'checked_out_items' => $selectedCartItems
            ], 200);
        }
    }




    public function destroy($id, Request $request)
    {
        // Periksa apakah user sudah terautentikasi
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Cari item cart berdasarkan ID dan user yang sedang login
        $cart = Cart::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        // Jika item cart tidak ditemukan
        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        // Hapus item cart
        $cart->delete();

        return response()->json(['message' => 'Cart item removed'], 200);
    }


    public function destroySelected(Request $request)
    {
        // Validasi input - pastikan cart_ids adalah string yang dipisahkan oleh koma
        $request->validate([
            'cart_id' => 'required|integer|exists:carts,id',
        ]);

        // Mengubah string cart_ids menjadi array integer
        $cartIds = explode(',', $request->input('cart_ids'));

        // Ambil data dari cart berdasarkan IDs yang diberikan
        $cartItems = Cart::where('user_id', Auth::id())
            ->whereIn('carts_id', $cartIds)
            ->get();

        // Jika tidak ada data ditemukan
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'No cart items found for the selected cart_ids'], 404);
        }

        // Hapus barang dari cart berdasarkan carts_id
        Cart::where('user_id', Auth::id())
            ->whereIn('carts_id', $cartIds)
            ->delete();

        // Kirim respons JSON yang benar
        return response()->json([
            'message' => 'Selected cart items removed',
            'deleted_items' => $cartItems // Menyertakan item yang dihapus dalam respons
        ], 200);
    }

}
