<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Requests\RegisterRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if ($user && $this->checkCakePhpPassword($credentials['password'], $user->password)) {
            // Re-hash the password with bcrypt
            $user->password = Hash::make($credentials['password']);
            $user->save();

            // Log the user in and generate a token
            Auth::login($user);
            $token = $user->createToken('Api Auth Token')->accessToken;

            return response(['token' => $token, 'user' => $user]);
        }
        if (Auth::attempt($credentials)) {
            $user = Auth::user()->load([
                'uploads',
                'usersPlatforms',
                'userUserTypes',
                'department',
                'role',
                'supervisor',
                'creator',
                'usersClients',
                'userDepartments.department',
            ]);
            // Generate an access token
            $token = $user->createToken('Api Auth Token')->accessToken;
            session([
                'Auth' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ]);
            return response()->json(['token' => $token, 'user' => $user], 200);
        }

        return response(['error' => 'Login request failed'], 422);
    }

    protected function checkCakePhpPassword($inputPassword, $hashedPassword)
    {
        // The salt from CakePHP's `Security.salt`
        $salt = '3e6af5971b7ab18dbaa35d1e166ec5e524420298';

        // Hash the input password with the salt using the same method as CakePHP
        $hashedInputPassword = sha1($salt . $inputPassword);

        // Compare the hashed input password with the hashed password from the database
        return $hashedInputPassword === $hashedPassword;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
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
