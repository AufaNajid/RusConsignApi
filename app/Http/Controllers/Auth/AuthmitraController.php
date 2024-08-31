<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\MitraResource;
use App\Models\Mitra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AuthmitraController extends Controller
{
    public function index(Request $request)
    {
        $query = Mitra::query();

        if ($request->has('nama_toko')) {
            $query->where('nama_toko', 'like', '%' . $request->query('nama_toko') . '%');
        }

        $mitras = $query->get();

        return MitraResource::collection($mitras);
    }

    public function show($id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        return new MitraResource($mitra);
    }
    public function registermitra(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "image_profile" => "image",
            "nama_lengkap" => "required|string",
            "nama_toko" => "required|string",
            "nis" => "required|integer|unique:mitras",
            "no_dompet_digital" => "required|string",
            "image_id_card" => "required|image",
            "status" => "string"
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle image upload
        if ($request->hasFile('image_id_card')) {
            $image = $request->file('image_id_card');

            $imageName = $image->getClientOriginalName();
            $mitraId = $request->input('mitra_id');
            $imagePath = "mitra_images/{$mitraId}_{$imageName}";

            $imagePath = $image->storeAs('mitra_images', $imageName);

            $imageIdCardPath = Storage::url($imagePath);
        } else {
            return response()->json(['message' => 'Image id card is required'], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $userEmail = $user->email;

        $mitra = new Mitra();
        $mitra->user_id = $user->id; // Menambahkan user_id
        $mitra->image_profile = $imageIdCardPath;
        $mitra->nama_lengkap = $request->nama_lengkap;
        $mitra->nama_toko = $request->nama_toko;
        $mitra->nis = $request->nis;
        $mitra->no_dompet_digital = $request->no_dompet_digital;
        $mitra->image_id_card = $imageIdCardPath;
        $mitra->status = $request->status ?? 'pending';
        $mitra->email = $userEmail;

        if ($mitra->save()) {
            $profileImage = $user->profileImages()->firstOrNew([]);
            $profileImage->mitra_id = $mitra->id;
            $profileImage->save();

            return new MitraResource($mitra);
        } else {
            return response()->json(['message' => 'Failed to register mitra'], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            "nama_lengkap" => "string",
            "nis" => "integer|unique:mitras,nis," . $id,
            "no_dompet_digital" => "string",
            "image_id_card" => "image",
            "status" => "string"
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('image_id_card')) {
            $image = $request->file('image_id_card');
            $imagePath = $image->store('mitra_images');
            $imageIdCardPath = Storage::url($imagePath);
            $mitra->image_id_card = $imageIdCardPath;
        }

        $mitra->nama_lengkap = $request->nama_lengkap ?? $mitra->nama_lengkap;
        $mitra->nis = $request->nis ?? $mitra->nis;
        $mitra->no_dompet_digital = $request->no_dompet_digital ?? $mitra->no_dompet_digital;
        $mitra->status = $request->status ?? $mitra->status;

        if ($mitra->save()) {
            return new MitraResource($mitra);
        } else {
            return response()->json(['message' => 'Failed to update mitra'], 500);
        }
    }


    public function destroy($id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        if ($mitra->delete()) {
            return response()->json(['message' => 'Mitra deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to delete mitra'], 500);
        }
    }
    public function accept(Request $request, $id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        // Change status to "accepted"
        $mitra->status = 'accepted';
        if ($mitra->save()) {
            return new MitraResource($mitra);
        } else {
            return response()->json(['message' => 'Failed to accept mitra'], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        // Change status to "rejected"
        $mitra->status = 'rejected';
        if ($mitra->save()) {
            return new MitraResource($mitra);
        } else {
            return response()->json(['message' => 'Failed to reject mitra'], 500);
        }
    }

    public function tambahpengikut($id)
    {
        $mitra = Mitra::find($id);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        $mitra->pengikut += 1;
        if ($mitra->save()) {
            return new MitraResource($mitra);
        } else {
            return response()->json(['message' => 'Failed to add pengikut'], 500);
        }
    }

    public function tambahproduct(Request $request, $mitraId)
    {
        $mitra = Mitra::find($mitraId);
        if (!$mitra) {
            return response()->json(['message' => 'Mitra not found'], 404);
        }

        $mitra->jumlah_product += 1;
        if ($mitra->save()) {
            return new MitraResource($mitra);
        } else {
            return response()->json(['message' => 'Failed to add product'], 500);
        }
    }

    public function tambahjasa()
    {

    }



}
