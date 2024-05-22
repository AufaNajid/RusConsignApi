<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function index()
    {
        $user = User::all();
        return response()->json($user);
    }
    public function register(Request $request)
    {

        //validation
      $request->validate([
        "name" => "required|string",
        "email" => "required|string|email|unique:users",
        "password" => "required|confirmed"
      ]);

      //user
      User::create([
        "name" => $request->name,
        "email"=> $request->email,
        "password"=> bcrypt($request->password)
      ]);

      return response()-> json([
        "status"=> true,
        "massage"=> "User registared succesfully",
        "data"=> []
      ]);

    }
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required|string|min:6'
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and password matches
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'These credentials do not match our records.',
                'data' => []
            ], 401);
        }

        // Create a personal access token
        $token = $user->createToken('my-app-token')->plainTextToken;

        // Send success response with token
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
            "massage" =>"User logged out",
            "data"=>[]
        ]);
    }

    public function datauser()
    {
        return view('/user', [
            "title" => "user",
        ]);
    }
}
