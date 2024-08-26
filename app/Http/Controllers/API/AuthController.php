<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Requests\RegisterRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            $user = Auth::user(); //this way we get whole user data
            $token = $user->createToken('Api Auth Token')->accessToken;
            return response(['token' => $token, 'user' => $user]);
        }

        return response(['error' => 'Login request failed'], 422);
    }

    public function register(RegisterRequest $request)
    {
        try{
            $data = $request->validated();
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $token = $user->createToken('Api Auth Token')->accessToken;
            return response(['user' => $user, 'token' => $token]);
        } catch (\Exception $e) {
            return response(['error' => sprintf("Error while signing up. Error: %s", $e->getMessage())]);
        }
    }

    public function logout(Request $request)
    {
        // Revoke the user's current access token
        $request->user()->tokens()->delete();
        return response()->json(['success']);
    }
}
