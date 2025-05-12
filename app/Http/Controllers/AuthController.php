<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // Register user
    public function register(Request $request)
    {
        // Validate fields
        $attrs = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed'
        ]);

        // Create user
        $user = User::create([
            'name' => $attrs['name'],
            'email' => $attrs['email'],
            'password' => bcrypt($attrs['password'])
        ]);

        // Return user & token in response
        return response([
            'user' => $user,
            'token' => $user->createToken('secret')->plainTextToken
        ], 200);
    }

    // Login User
    public function login(Request $request)
{
    // Validasi input
    $attrs = $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:6'
    ]);

    // Coba login dengan email dan password
    if (!Auth::attempt($attrs)) {
        return response([
            'message' => 'Invalid credentials.'
        ], 403);
    }

    // Kembalikan data user dan token
    return response([
        'user' => auth()->user(),
        'token' => auth()->user()->createToken('secret')->plainTextToken
    ], 200);
}


    // Logout User
    public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Logged out successfully',
    ]);
}
    
    public function user(){
        return response([
            'user' => auth()->user()
        ], 200);
    }
}
