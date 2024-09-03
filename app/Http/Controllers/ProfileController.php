<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use App\Models\Mitra;
use App\Models\Product;
use App\Models\ProfileImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Profiler\Profile;

class ProfileController extends Controller
{

    public function editProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Validasi data yang diterima
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio_desc' => 'sometimes|string',
            'image_profile' => 'sometimes|image',
            'nama_toko' => 'sometimes|string|max:255'
        ]);

        // Update data user
        if (isset($validatedData['name'])) {
            $user->name = $validatedData['name'];
        }
        if (isset($validatedData['bio_desc'])) {
            $user->bio_desc = $validatedData['bio_desc'];
        }

        // Update atau tambahkan profile image
        if (isset($validatedData['image_profile'])) {
            $profileImage = $user->profileImages()->first();
            if ($profileImage) {
                // Hapus gambar lama jika ada
                if (Storage::exists($profileImage->image_profile)) {
                    Storage::delete($profileImage->image_profile);
                }
                // Simpan gambar baru
                $imagePath = $request->file('image_profile')->store('public/profiles');
                $profileImage->image_profile = Storage::url($imagePath);
                $profileImage->save();
            } else {
                // Buat gambar profil baru jika belum ada
                $imagePath = $request->file('image_profile')->store('public/profiles');
                $profileImage = $user->profileImages()->create([
                    'image_profile' => Storage::url($imagePath),
                    'mitra_id' => null,
                ]);
            }
        }

        // Update nama toko dalam tabel mitra jika ada
        if (isset($validatedData['nama_toko'])) {
            $profileImage = $user->profileImages()->first(); // Dapatkan gambar profil pertama
            if ($profileImage && $profileImage->mitra) {
                $profileImage->mitra->nama_toko = $validatedData['nama_toko'];
                $profileImage->mitra->save();
            }
        }

        // Simpan perubahan pada data user
        $user->save();

        // Mengembalikan respon sukses
        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user,
        ], 200);
    }


    public function postImageProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validatedData = $request->validate([
            'image_profile' => 'required|image',
        ]);

        $imagePath = $request->file('image_profile')->store('public/profiles');
        $imageProfileUrl = Storage::url($imagePath);

        $profileImage = $user->profileImages()->create([
            'image_profile' => $imageProfileUrl,
            'mitra_id' => null, // Explicitly set mitra_id to null
        ]);

        return response()->json([
            'message' => 'Image profile berhasil diunggah',
            'profile_image' => $profileImage,
        ], 201);
    }

    public function editImageProfile(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Temukan gambar profil berdasarkan ID
        $profileImage = $user->profileImages()->findOrFail($id);

        // Validasi data
        $validatedData = $request->validate([
            'image_profile' => 'required|image',
        ]);

        // Hapus gambar lama
        if (Storage::exists($profileImage->image_profile)) {
            Storage::delete($profileImage->image_profile);
        }

        // Simpan gambar baru
        $imagePath = $request->file('image_profile')->store('public/profiles');
        $profileImage->image_profile = Storage::url($imagePath);
        $profileImage->save();

        return response()->json([
            'message' => 'Image profile berhasil diperbarui',
            'profile_image' => $profileImage,
        ], 200);
    }

    public function destroyImageProfile($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $profileImage = $user->profileImages()->findOrFail($id);

        // Delete the image from storage
        if (Storage::exists($profileImage->image_profile)) {
            Storage::delete($profileImage->image_profile);
        }

        $profileImage->delete();

        return response()->json([
            'message' => 'Image profile berhasil dihapus',
        ], 200);
    }

}

