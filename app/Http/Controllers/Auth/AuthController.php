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

    public function index(Request $request)
    {
        if ($request->has('username')) {
            $username = $request->input('username');
            $users = User::where('name', 'LIKE', "%$username%")->get();
        } else {
            $users = User::all();
        }

        return response()->json($users);
    }
    public function register(Request $request)
    {

        //validation
      $request->validate([
        "name" => "required|string",
        "email" => "required|string|email|unique:users",
        "password" => "required|string"
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


}
