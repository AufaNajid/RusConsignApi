<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\MitraResource;
use App\Models\Admin;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Hash;


class AuthadminController extends Controller
{
    public function registeradmin(Request $request)
    {
        $request->validate([
            "email" => "required|string|email|unique:admins,email",
            "password" => "required"
        ]);

        Admin::create([
            "email" => $request->email,
            "password" => bcrypt($request->password),
        ]);

        return response()->json([
            "status" => true,
            "message" => "Admin registered successfully",
            "data" => []
        ]);
    }

    public function loginadmin(Request $request)
    {
        // Validasi
        $request->validate([
            "email" => "required|email|string",
            "password" => "required"
        ]);

        $user = Admin::where("email", $request->email)->first();

        if (!empty($user)) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken("mytoken")->plainTextToken;

                return response()->json([
                    "status" => true,
                    "message" => "User logged in",
                    "token" => $token,
                    "data" => []
                ]);
            } else {
                return response()->json([
                    "status" => false,
                    "message" => "Invalid password",
                    "data" => []
                ]);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Email doesn't match with records",
                "data" => []
            ]);
        }
    }
}

