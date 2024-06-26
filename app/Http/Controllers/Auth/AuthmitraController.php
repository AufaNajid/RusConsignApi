<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\MitraResource;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AuthmitraController extends Controller
{
    public function index()
    {
        $mitras = Mitra::all();
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
            "nama_lengkap" => "required|string",
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
            $imagePath = $image->store('mitra_images');
            $imageIdCardPath = Storage::url($imagePath);
        } else {
            return response()->json(['message' => 'Image id card is required'], 422);
        }

        $mitra = new Mitra();
        $mitra->nama_lengkap = $request->nama_lengkap;
        $mitra->nis = $request->nis;
        $mitra->no_dompet_digital = $request->no_dompet_digital;
        $mitra->image_id_card = $imageIdCardPath;
        $mitra->status = $request->status ?? 'pending';

        if ($mitra->save()) {
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


}
