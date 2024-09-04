<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index()
    {
        $lokasi = Lokasi::all();

        return response()->json($lokasi);
    }

    public function show($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        return response()->json($lokasi);
    }
    public function lokasi(Request $request)
    {
        $validatedData = $request->validate([
            'nama_lokasi' => 'required|string|max:255',
            'desc_lokasi' => 'required|string',
            'gambar_lokasi' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('gambar_lokasi')) {
            $image = $request->file('gambar_lokasi');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('images', $imageName, 'public');

            $lokasi = new Lokasi();
            $lokasi->nama_lokasi = $validatedData['nama_lokasi'];
            $lokasi->desc_lokasi = $validatedData['desc_lokasi'];
            $lokasi->gambar_lokasi = '/storage/' . $imagePath;
            $lokasi->save();

            return response()->json(['message' => 'Location added successfully'], 200);
        } else {
            return response()->json(['message' => 'Image upload failed'], 400);
        }
    }
}
