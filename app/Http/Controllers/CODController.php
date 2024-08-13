<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Cod;
use App\Models\Lokasi;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CODController extends Controller
{
    public function index()
    {
        $cods = Cod::with(['barang', 'lokasi.mitra', 'user'])->get();

        return response()->json([
            'cods' => $cods,
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'barang_id' => 'required|exists:barangs,id',
            'lokasi_id' => 'required|exists:lokasis,id',
            'quantity' => 'required|integer',
            'mitra_id' => 'required|exists:mitras,id',
        ]);

        $barang = Barang::findOrFail($validatedData['barang_id']);
        $lokasi = Lokasi::findOrFail($validatedData['lokasi_id']);
        $user = Auth::user();

        if ($barang->stock < $validatedData['quantity']) {
            return response()->json(['message' => 'Stok barang tidak mencukupi'], 400);
        }

        $totalAmount = $barang->harga * $validatedData['quantity'];

        // Update the stock
        $barang->stock -= $validatedData['quantity'];
        $barang->save();

        // Create COD record
        $cod = Cod::create([
            'barang_id' => $validatedData['barang_id'],
            'lokasi_id' => $validatedData['lokasi_id'],
            'quantity' => $validatedData['quantity'],
            'status_pembayaran' => 'belum_pembayaran',
            'grand_total' => $totalAmount,
            'user_id' => $user->id,
            'mitra_id' => $validatedData['mitra_id'],
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil ditambahkan',
            'cod' => $cod,
            'product' => $barang,
            'lokasi' => $lokasi,
            'user' => $user
        ], 201);
    }


        public function updateStatus(Request $request, $id)
        {
            $validatedData = $request->validate([
                'status_pembayaran' => 'required|string|in:belum_pembayaran,progres',
            ]);

            $cod = Cod::findOrFail($id);

            $cod->status_pembayaran = $validatedData['status_pembayaran'];
            $cod->save();

            $user = $cod->user;
            if ($user) {
                $user->status_pembayaran = $validatedData['status_pembayaran'];
                $user->save();
            }

            $mitra = $cod->lokasi->mitra;
            if ($mitra) {
                $mitra->status_pembayaran = $validatedData['status_pembayaran'];
                $mitra->save();
            }

            return response()->json(['message' => 'Status pembayaran berhasil diupdate'], 200);
        }

    public function getUserCods($userId = null)
    {
        // Jika $userId tidak diberikan, gunakan ID pengguna yang sedang login
        if (!$userId) {
            $userId = Auth::id();
        }

        // Cek apakah pengguna ada
        $user = User::findOrFail($userId);

        // Ambil semua COD terkait dengan pengguna tersebut
        $cods = $user->cods()->with(['barang', 'lokasi.mitra'])->get();

        return response()->json([
            'user' => $user,
            'cods' => $cods,
        ]);
    }

    public function getMitraCods($mitraId)
    {
        $mitra = Mitra::find($mitraId);

        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        $cods = Cod::whereIn('lokasi_id', $mitra->lokasis->pluck('id'))->with(['barang', 'user'])->get();

        return response()->json([
            'mitra' => $mitra,
            'cods' => $cods,
        ]);
    }

    public function updateStatusToCompleted(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status_pembayaran' => 'required|string|in:selesai',
        ]);

        $cod = Cod::findOrFail($id);

        if ($cod->status_pembayaran !== 'progres') {
            return response()->json(['message' => 'Status pembayaran harus dalam progres sebelum diupdate ke selesai'], 400);
        }

        $cod->status_pembayaran = $validatedData['status_pembayaran'];
        $cod->save();

        $user = $cod->user;
        if ($user) {
            $user->status_pembayaran = $validatedData['status_pembayaran'];
            $user->save();
        }

        $mitra = $cod->lokasi->mitra;
        if ($mitra) {
            $mitra->status_pembayaran = $validatedData['status_pembayaran'];
            $mitra->save();
        }

        return response()->json(['message' => 'Status pembayaran berhasil diperbarui menjadi selesai'], 200);
    }

    public function getCodsByStatus($role, $status, $id)
    {
        if (!in_array($role, ['user', 'mitra'])) {
            return response()->json(['message' => 'Role tidak valid'], 400);
        }

        if (!in_array($status, ['belum_pembayaran', 'progres', 'selesai'])) {
            return response()->json(['message' => 'Status pembayaran tidak valid'], 400);
        }

        if ($role === 'user') {
            $cods = Cod::where('status_pembayaran', $status)
                ->where('user_id', $id)
                ->with(['barang', 'lokasi.mitra', 'user'])
                ->get();
        } else {
            $cods = Cod::where('status_pembayaran', $status)
                ->where('mitra_id', $id)
                ->with(['barang', 'lokasi.mitra', 'user'])
                ->get();
        }

        return response()->json([
            'role' => $role,
            'status' => $status,
            'cods' => $cods,
        ], 200);
    }
}
