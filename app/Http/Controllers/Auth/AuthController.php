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
        $request->validate([
            "name" => "required|string",
            "email" => "required|string|email|unique:users",
            "password" => "required|string|min:6"
        ]);

//        $verificationToken = Str::random(60);

        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => bcrypt($request->password),
            "mitra_id" => 0,
//            "email_verification_token" => $verificationToken
        ]);

//        Mail::to($user->email)->send(new VerificationMail($user, $verificationToken));

        return response()->json([
            "status" => true,
            "message" => "User registered successfully",
            "data" => [
                "user" => $user
            ]
        ]);
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required|string|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'These credentials do not match our records.',
                'data' => []
            ], 401);
        }

        Auth::login($user);

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

    public function reset(Request $request)
    {
        $request->validate([
            'password' => 'required|confirmed|min:6',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }

//    public function verifyEmail(Request $request, $token)
//    {
//        $user = User::where('email_verification_token', $token)->first();
//
//        if (!$user) {
//            return response()->json(['message' => 'Invalid or expired verification token'], 404);
//        }
//
//        if ($user->email_verified_at) {
//            return response()->json(['message' => 'Email already verified'], 400);
//        }
//
//        $user->email_verified_at = now();
//        $user->email_verification_token = null;
//        $user->save();
//
//        return response()->json(['message' => 'Email verified successfully']);
//    }
//
//    // Fungsi kirim ulang verifikasi
//    public function resendVerification(Request $request)
//    {
//        $request->validate(['email' => 'required|email']);
//        $user = User::where('email', $request->email)->first();
//
//        if (!$user) {
//            return response()->json(['message' => 'User not found'], 404);
//        }
//
//        if ($user->email_verified_at) {
//            return response()->json(['message' => 'Email already verified'], 400);
//        }
//
//        $verificationToken = Str::random(60);
//        $user->update(['email_verification_token' => $verificationToken]);
//
//        Mail::to($user->email)->send(new VerificationMail($user, $verificationToken));
//
//        return response()->json(['message' => 'Verification email resent successfully']);
//    }
}

