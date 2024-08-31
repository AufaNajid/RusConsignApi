<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerificationMail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('email')) {
            $email = $request->input('email');
            $users = User::where('email', 'LIKE', "%{$email}%")->get();
        } else {
            $users = User::all();
        }

        return response()->json($users);
    }

    public function register(Request $request)
    {
        // Validasi input
        $request->validate([
            "name" => "required|string",
            "email" => "required|string|email|unique:users",
            "password" => "required|string|min:6"
        ]);

        // Membuat token verifikasi email
        $verificationToken = Str::random(60);

        // Membuat user baru
        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => bcrypt($request->password),
            "mitra_id" => 0, // Tetapkan mitra_id menjadi 0 secara default
            "email_verification_token" => $verificationToken // Simpan token verifikasi
        ]);

        // Mengirim email verifikasi
        Mail::to($user->email)->send(new VerificationMail($user, $verificationToken));

        return response()->json([
            "status" => true,
            "message" => "User registered successfully. Please check your email for verification.",
            "data" => [
                "user" => $user
            ]
        ]);
    }


    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required|string|min:6'
        ]);

        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // Periksa apakah user ada dan password cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'These credentials do not match our records.',
                'data' => []
            ], 401);
        }

        // Pastikan user berhasil diautentikasi
        Auth::login($user);

        // Buat token akses pribadi setelah user berhasil diotentikasi
        $token = $user->createToken('my-app-token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User logged in',
            'token' => $token,
            'data' => [
                'user' => $user
            ]
        ]);
    }

    public function profile(Request $request)
    {
        $userData = auth()->user();
        $isMitra = $userData->status == 'mitra';

        return response()->json([
            "status" => true,
            "message" => "Profile Information",
            "data" => $userData,
            "is_mitra" => $isMitra,
            "id" => auth()->user()->id
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            "status" => true,
            "message" => "User logged out",
            "data" => []
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                "status" => false,
                "message" => "User not found",
                "data" => []
            ], 404);
        }

        if ($user->delete()) {
            return response()->json([
                "status" => true,
                "message" => "User deleted successfully",
                "data" => []
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Failed to delete user",
                "data" => []
            ], 500);
        }
    }

    public function editBio(Request $request, $user_id)
    {
        // Find the user by user_id
        $user = User::find($user_id);

        // Check if the user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Validate the request data
        $validatedData = $request->validate([
            'bio_desc' => 'required|string',
        ]);

        // Update the bio_desc
        $user->bio_desc = $validatedData['bio_desc'];

        // Save the changes
        if ($user->save()) {
            return response()->json(['message' => 'Bio description updated successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to update bio description'], 500);
        }
    }

    public function verifyEmail(Request $request, $token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired verification token'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return response()->json(['message' => 'Email verified successfully']);
    }


    public function resendVerification(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $verificationToken = Str::random(60);
        $user->update(['email_verification_token' => $verificationToken]);

        Mail::to($user->email)->send(new VerificationMail($user, $verificationToken));

        return response()->json(['message' => 'Verification email resent successfully']);
    }

    public function apiVerifyEmail(Request $request, $token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid verification token'], 404);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function sendResetPasswordEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $token = Str::random(60);
        $user->reset_password_token = $token;
        $user->save();

        $resetLink = url('/reset-password/' . $token);
        Mail::to($user->email)->send(new ResetPasswordMail($user, $resetLink));

        return response()->json(['message' => 'Reset password email sent']);
    }

    public function resetpassprofile(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password has been updated successfully']);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('reset_password_token', $request->token)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Invalid reset token.');
        }

        $user->password = Hash::make($request->password);
        $user->reset_password_token = null;
        $user->save();

        return view('completeresetpass');
    }
}
