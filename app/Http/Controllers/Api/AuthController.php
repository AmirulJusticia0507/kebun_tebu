<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Issue API Sanctum Bearer Token / OAuth Token
     */
    public function issueToken(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kombinasi email dan password tidak sesuai.'],
            ]);
        }

        $deviceName = $request->device_name ?? 'mobile_gis_client';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'token_type'   => 'Bearer',
            'access_token' => $token,
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * Get Authenticated User Details via Bearer Token
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'role'         => $user->role,
                'phone_number' => $user->phone_number,
                'permissions'  => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * Revoke Current Token (Logout)
     */
    public function revokeToken(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Token API berhasil dicabut (logged out).',
        ]);
    }
}
