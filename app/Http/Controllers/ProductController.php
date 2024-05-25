<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Mitra;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    public function index()
    {
        $product = Product::all();
        return ProductResource::collection($product);
    }

    public function addProduct(Request $request)
    {
        // Validasi data yang diterima dari request
        $validatedData = $request->validate([
            'name_product' => 'required|string',
            'desc_product' => 'required|string',
            'price_product' => 'required|numeric',
            'rating_product' => 'required|numeric',
            'image' => 'required|image',
            'mitra_id' => 'required|exists:mitras,id',
        ]);

        // Mencari data mitra berdasarkan mitra_id
        $mitra = Mitra::find($validatedData['mitra_id']);

        // Memeriksa status mitra
        if ($mitra->status !== 'accepted') {
            return response()->json(['message' => 'Mitra not accepted'], 403);
        }

        // Simpan produk ke dalam database
        $product = new Product();
        $product->name_product = $validatedData['name_product'];
        $product->desc_product = $validatedData['desc_product'];
        $product->price_product = $validatedData['price_product'];
        $product->rating_product = $validatedData['rating_product'];

        // Mengelola unggah dan penyimpanan gambar
        $imagePath = $request->file('image')->store('public/images');
        $product->image = basename($imagePath);
        $product->mitra_id = $validatedData['mitra_id'];
        $product->save();

        // Menambah jumlah_product pada mitra
        $mitra->jumlah_product += 1;
        $mitra->save();

        // Mengembalikan respons JSON yang menunjukkan keberhasilan
        return response()->json(['message' => 'Produk berhasil ditambahkan', 'product' => $product], 201);
    }


    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validatedData = $request->validate([
            'name_product' => 'required|string',
            'desc_product' => 'required|string',
            'price_product' => 'required|numeric',
            'rating_product' => 'required|numeric',
            'image' => 'image'
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('product_images');
            $product->image = $imagePath;
        }

        // Update product attributes
        $product->name_product = $validatedData['name_product'] ?? $product->name_product;
        $product->desc_product = $validatedData['desc_product'] ?? $product->desc_product;
        $product->price_product = $validatedData['price_product'] ?? $product->price_product;
        $product->rating_product = $validatedData['rating_product'] ?? $product->rating_product;

        // Save the updated product
        if ($product->save()) {
            return response()->json(['message' => 'Product updated successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to update product'], 500);
        }
    }
    public function destroy($id)
    {
        // Find the product by ID
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Delete the product
        if ($product->delete()) {
            return response()->json(['message' => 'Product deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to delete product'], 500);
        }
    }
    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('image', compact('product'));
    }

}
