<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return response()->json([
                'message' => 'Invalid verification link.',
                'data' => null,
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'data' => [
                    'verified' => true,
                    'email' => $user->email,
                    'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
                ],
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully.',
            'data' => [
                'verified' => true,
                'email' => $user->email,
                'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
            ],
        ]);
    }

    public function resend(Request $request)
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json([
                'message' => 'User not authenticated',
                'data' => null,
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'data' => [
                    'verified' => true,
                    'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
                ],
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
            'data' => [
                'verified' => false,
            ],
        ]);
    }

    public function status(Request $request)
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json([
                'message' => 'User not authenticated',
                'data' => null,
            ], 401);
        }

        return response()->json([
            'message' => 'Verification status',
            'data' => [
                'verified' => $user->hasVerifiedEmail(),
                'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
            ],
        ]);
    }
}
