<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => 'required|string|max:15|unique:users,phone',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'id'       => Str::uuid(),
            'name'     => trim($request->name),
            'phone'    => trim($request->phone),
            'email'    => $request->email ?: null,
            'password' => Hash::make($request->password),
            'role'     => 'buyer',
        ]);

        $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;

        return Response::json([
            'success' => true,
            'message' => 'Account created successfully',
            'data'    => ['user' => $this->userResource($user), 'token' => $token],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $identifier = $request->phone ?? $request->email;

        if (!$identifier) {
            return Response::json([
                'success' => false,
                'message' => 'Phone or email required',
            ], 400);
        }

        $user = User::where('phone', $identifier)
                    ->orWhere('email', $identifier)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return Response::json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user->is_active) {
            return Response::json([
                'success' => false,
                'message' => 'Your account has been suspended',
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;
        $shop  = $user->shop()->select('id', 'name', 'slug', 'logo', 'status')->first();

        return Response::json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => ['user' => $this->userResource($user), 'token' => $token, 'shop' => $shop],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $shop = $request->user()->shop()->select('id', 'name', 'slug', 'logo', 'status')->first();

        return Response::json([
            'success' => true,
            'data'    => ['user' => $this->userResource($request->user()), 'shop' => $shop],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'   => 'sometimes|string|max:100',
            'email'  => 'sometimes|nullable|email|unique:users,email,' . $request->user()->id,
            'avatar' => 'sometimes|nullable|string',
        ]);

        $request->user()->update($request->only('name', 'email', 'avatar'));

        return Response::json([
            'success' => true,
            'data'    => $this->userResource($request->user()->fresh()),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return Response::json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $request->user()->update(['password' => Hash::make($request->new_password)]);

        return Response::json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return Response::json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    private function userResource(User $user): array
    {
        return [
            'id'        => $user->id,
            'name'      => $user->name,
            'phone'     => $user->phone,
            'email'     => $user->email,
            'role'      => $user->role,
            'avatar'    => $user->avatar,
            'is_active' => (bool) $user->is_active,
        ];
    }
}
