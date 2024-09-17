<?php

namespace App\Http\Controllers;

use App\Models\Komentar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'barang_id' => 'required|exists:barangs,id'
        ]);

        $barangId = $validatedData['barang_id'];

        $rate = Komentar::select(
            DB::raw('count(1) as total'),
            'rate'
        )
            ->where('barang_id', $barangId)
            ->groupBy('rate')
            ->get();

        $total = $rate->sum('total');
        $avg = $rate->reduce(function ($carry, $item) {
                return $carry + ($item->total * $item->rate);
            }, 0) / ($total ?: 1);


        $reviews = Komentar::with('user')
        ->where('barang_id', $barangId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'summary' => [
                'total' => $total,
                'avg' => $avg,
            ],
            'rates' => $rate,
            'reviews' => $reviews
        ]);
    }

    public function store(Request $request)
    {
        // Validasi data input
        $validatedData = $request->validate([
            'barang_id' => 'required|exists:barangs,id',
            'komentar' => 'required|string',
            'rate' => 'required|integer|min:1|max:5',
        ]);


        $komentar = new Komentar();
        $komentar->user_id = auth()->user()->id;
        $komentar->barang_id = $validatedData['barang_id'];
        $komentar->komentar = $validatedData['komentar'];
        $komentar->rate = $validatedData['rate'];
        $komentar->save();



        return response()->json([
        'success' => true,
        'message' => 'Komentar berhasil disimpan!',
            'data' => [
                'komentar' => $komentar,
                'user' => $komentar->user,
            ]
    ], 201);
}
}
