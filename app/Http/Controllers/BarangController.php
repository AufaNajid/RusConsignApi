<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarangResource;
use App\Http\Resources\MitraResource;
use App\Models\Barang;
use App\Models\Category;
use App\Models\Komentar;
use App\Models\Mitra;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BarangController extends Controller
{

    public function filterProductsByMitra(Request $request)
    {
        $request->validate([
            'mitra_id' => 'required|integer|exists:mitras,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $mitraId = $request->input('mitra_id');
        $categoryId = $request->input('category_id');

        $query = Barang::where('mitra_id', $mitraId)
            ->with('category:id,name', 'mitra:id,nama_lengkap,nama_toko,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $barangs = $query->get();

        if ($barangs->isEmpty()) {
            return response()->json(['message' => 'Tidak ada barang yang ditemukan untuk mitra ini'], 404);
        }

        $barangData = [];
        foreach ($barangs as $barang) {
            $barangData[] = [
                'id_barang' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'deskripsi' => $barang->deskripsi,
                'harga' => $barang->harga,
                'rating_barang' => $barang->rating_barang,
                'category_id' => $barang->category->id,
                'category_name' => $barang->category->name,
                'image_barang' => $barang->image_barang,
                'stock' => $barang->stock_barang,
                'status_post' => $barang->status_post,
                'quantity' => $barang->quantity,
                'created_at' => $barang->created_at,
                'updated_at' => $barang->updated_at,
            ];
        }

        return response()->json([
            'message' => 'Data barang berhasil ditemukan',
            'barangs' => $barangData,
        ], 200);
    }



    public function searchAcceptedBarangs(Request $request)
    {
        $searchTerm = $request->query('q');

        if (!$searchTerm) {
            return response()->json(['message' => 'Kata kunci pencarian harus diberikan'], 400);
        }

        $categoryId = $request->query('category_id');

        $query = Barang::where('status_post', 'publish')
            ->with('category:id,name', 'mitra:id,nama_lengkap,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp')
            ->where(function ($query) use ($searchTerm) {
                $query->where('nama_barang', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('deskripsi', 'LIKE', "%{$searchTerm}%");
            });

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $barangs = $query->get();

        if ($barangs->isEmpty()) {
            return response()->json(['message' => 'Tidak ada barang yang ditemukan dengan kata kunci ini'], 404);
        }

        $barangData = [];
        foreach ($barangs as $barang) {
            $barangData[] = [
                'id' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'deskripsi' => $barang->deskripsi,
                'harga' => $barang->harga,
                'rating_barang' => $barang->rating_barang,
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
                    'no_whatsapp'=> $barang->mitra->no_whatsapp,
                ],
            ];
        }

        return response()->json([
            'message' => 'Barang yang diterima ditemukan dengan kata kunci pencarian',
            'barangs' => $barangData,
        ], 200);
    }

    public function getAcceptedBarangs(Request $request)
    {
        $categoryId = $request->query('category_id');

        $query = Barang::where('status_post', 'publish')
            ->with('category:id,name', 'mitra:id,nama_lengkap,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $barangs = $query->get();

        if ($barangs->isEmpty()) {
            return response()->json(['message' => 'Tidak ada barang yang diterima ditemukan'], 404);
        }

        $barangData = [];
        foreach ($barangs as $barang) {
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

            $barangData[] = [
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
                    'no_whatsapp'=> $barang->mitra->no_whatsapp,
                ],
            ];
        }

        return response()->json([
            'message' => 'Data barang yang diterima berhasil ditemukan',
            'barangs' => $barangData,
        ], 200);
    }


    public function filterProductsByCategory(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $categoryId = $request->input('category_id');

        $barangs = Barang::where('category_id', $categoryId)
            ->with('category:id,name', 'mitra:id,nama_lengkap,nama_toko,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp',)
            ->get();

        if ($barangs->isEmpty()) {
            return response()->json(['message' => 'Tidak ada barang yang ditemukan untuk kategori ini'], 404);
        }

        $barangData = [];
        foreach ($barangs as $barang) {
            $barangData[] = [
                'id' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'deskripsi' => $barang->deskripsi,
                'harga' => $barang->harga,
                'rating_barang' => $barang->rating_barang,
                'category_id' => $barang->category->id,
                'category_nama' => $barang->category->name,
                'image_barang' => $barang->image_barang,
                'stock' => $barang->stock_barang,
                'quantity' => $barang->quantity,
                'status_post' => $barang->status_post,
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
                    'no_whatsapp'=> $barang->mitra->no_whatsapp,
                ],
            ];
        }

        return response()->json([
            'message' => 'Data barang berdasarkan kategori berhasil ditemukan',
            'barangs' => $barangData,
        ], 200);
    }
    public function index()
    {
        $barangs = Barang::with('category:id,name', 'mitra:id,nama_lengkap,nama_toko,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp,email')->get();

        if ($barangs->isEmpty()) {
            return response()->json(['message' => 'Tidak ada barang yang ditemukan'], 404);
        }


        $barangData = [];
        foreach ($barangs as $barang) {
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

            $barangData[] = [
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
                    'no_whatsapp'=> $barang->mitra->no_whatsapp,
                    'email' => $barang->mitra->email
                ],
            ];
        }

        return response()->json([
            'message' => 'Data barang berhasil ditemukan',
            'barangs' => $barangData,
        ], 200);
    }

    public function show($id)
    {
        $barang = Barang::with('category:id,name', 'mitra:id,nama_lengkap,nama_toko,jumlah_product,jumlah_jasa,pengikut,penilaian,no_whatsapp,email,image_profiles')->find($id);

        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
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

        $barangData = [
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
            'status_post' => $barang->status_post,
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
                'no_whatsapp'=> $barang->mitra->no_whatsapp,
                'image_profiles' => $barang->mitra->profileImages->first()->image_profile ?? null
            ],
        ];

        return response()->json([
            'message' => 'Data barang berhasil ditemuka  n',
            'barang' => $barangData,
        ], 200);
    }

    public function addBarang(Request $request)
        {
            $validatedData = $request->validate([
                'nama_barang' => 'required|string|max:255',
                'deskripsi' => 'required|string',
                'harga' => 'required|integer',
                'rating_barang' => 'numeric',
                'category_id' => 'required|in:1,2',
                'image_barang' => 'required|image',
                'stock_barang'=>'required|integer',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $profileImage = $user->profileImages()->first();
            if (!$profileImage) {
                return response()->json(['message' => 'Profile image not found'], 404);
            }


            if ($request->hasFile('image_barang')) {
                $image = $request->file('image_barang');

                $imageName = $image->getClientOriginalName();
                $mitraId = $request->input('mitra_id');
                $imagePath = "product_images/{$mitraId}_{$imageName}";

                $imagePath = $image->storeAs('public/product_images', $imageName);

                $imageProductPath = Storage::url($imagePath);
            }


            $mitraId = $profileImage->mitra_id;
            $mitra = Mitra::find($mitraId);
            if (!$mitra) {
                return response()->json(['message' => 'Mitra not found'], 404);
            }

            $imagePath = $request->file('image_barang')->store('public/images');
            $barang = new Barang();
            $barang->nama_barang = $validatedData['nama_barang'];
            $barang->deskripsi = $validatedData['deskripsi'];
            $barang->harga = $validatedData['harga'];
            $barang->rating_barang = $validatedData['rating_barang'] ?? 0.0;
            $barang->category_id = $validatedData['category_id'];
            $barang->image_barang = $imageProductPath;
            $barang->mitra_id = $mitraId;
            $barang->status_post = $validatedData->status_post ?? 'pending';
            $barang->stock_barang = $validatedData['stock_barang'] ?? 0;
            $barang->quantity = 1;
            $barang->save();

            // Retrieve the category
            $category = Category::find($validatedData['category_id']);
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            $categoryName = $category->name;

            $categoryType = $validatedData['category_id'] == 1 ? 'product' : 'jasa';
            $mitra->{"jumlah_$categoryType"} += 1;
            $mitra->save();

            $mitraData = [
                'id' => $mitra->id,
                'name' => $mitra->nama_lengkap,
                'jumlah_product' => $mitra->jumlah_product,
                'jumlah_jasa' => $mitra->jumlah_jasa,
                'pengikut' =>$mitra->pengikut,
                'penilaian' => $mitra->penilaian,
            ];



            return response()->json([
                'message' => 'Produk berhasil ditambahkan',
                'product' => $barang,
                'mitra' => $mitraData,
                'category_name' => $categoryName,
            ], 201);
        }

    public function editBarang(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nama_barang' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'nullable|integer',
            'rating_barang' => 'nullable|numeric',
            'category_id' => 'nullable|in:1,2',
            'image_barang' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validasi gambar
            'stock_barang' => 'nullable|numeric',
        ]);

        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        $barang->fill($validatedData);


        if ($request->hasFile('image_barang')) {
            if ($barang->image_barang) {
                $oldImagePath = str_replace('/storage/', '', $barang->image_barang);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            // Simpan gambar baru
            $image = $request->file('image_barang');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('product_images', $imageName, 'public');
            $barang->image_barang = Storage::url($imagePath);
        }

        $barang->save();

        $category = $barang->category;
        $mitra = $barang->mitra;

        $categoryName = $category ? $category->name : 'Category not found';
        $mitraData = $mitra ? [
            'id' => $mitra->id,
            'name' => $mitra->nama_lengkap,
            'nama_toko' => $mitra->nama_toko,
            'jumlah_product' => $mitra->jumlah_product,
            'jumlah_jasa' => $mitra->jumlah_jasa,
            'pengikut' => $mitra->pengikut,
            'penilaian' => $mitra->penilaian,
        ] : null;

        return response()->json([
            'message' => 'Produk berhasil diperbarui',
            'product' => $barang,
            'mitra' => $mitraData,
            'category_name' => $categoryName,
        ], 200);
    }


    public function deleteBarang(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        $profileImage = $user->profileImages()->first();
        if (!$profileImage) {
            return response()->json(['message' => 'Profile image not found'], 404);
        }

        if ($barang->mitra_id !== $profileImage->mitra_id) {
            return response()->json(['message' => 'Tidak diizinkan untuk menghapus barang ini'], 403);
        }

        $mitra = Mitra::find($barang->mitra_id);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        if ($barang->category_id == 1) {
            $mitra->jumlah_product = max(0, $mitra->jumlah_product - 1); // Pastikan tidak negatif
        } elseif ($barang->category_id == 2) {
            $mitra->jumlah_jasa = max(0, $mitra->jumlah_jasa - 1); // Pastikan tidak negatif
        }

        $mitra->save();

        $barang->delete();

        return response()->json([
            'message' => 'Barang berhasil dihapus',
        ], 200);
    }

    public function getBarangsByMitraId($mitra_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $barangs = Barang::where('mitra_id', $mitra_id)->with('category:id,name')->get();

        if ($barangs->isEmpty()) {
            return response()->json(['message' => 'Tidak ada barang yang ditemukan untuk mitra ini'], 404);
        }

        $barangData = [];
        foreach ($barangs as $barang) {
            $barangData[] = [
                'id_barang' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'deskripsi' => $barang->deskripsi,
                'harga' => $barang->harga,
                'rating_barang' => $barang->rating_barang,
                'status_post'=> $barang->status_post,
                'category_id' => $barang->category->id,
                'category_name' => $barang->category->name,
                'image_barang' => $barang->image_barang,
                'created_at' => $barang->created_at,
                'updated_at' => $barang->updated_at,
            ];
        }

        return response()->json([
            'message' => 'Data barang berhasil ditemukan',
            'barangs' => $barangData,
        ], 200);
    }

    public function publish(Request $request, $id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang not found'], 404);
        }

        $barang->status_post = 'publish';
        if ($barang->save()) {
            return new BarangResource($barang);
        } else {
            return response()->json(['message' => 'Failed to reject admin'], 500);
        }
    }

    public function unpublish(Request $request, $id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json(['message' => 'Barang not found'], 404);
        }

        // Change status to "rejected"
        $barang->status_post = 'unpublish';
        if ($barang->save()) {
            return new BarangResource($barang);
        } else {
            return response()->json(['message' => 'Failed to reject admin'], 500);
        }

    }
}
