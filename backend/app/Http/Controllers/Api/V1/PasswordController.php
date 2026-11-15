<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordController extends Controller
{
    /**
     * Sends a reset link to the web reset page. Always answers the same way, so the
     * endpoint cannot be used to discover which emails have an account.
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => mb_strtolower($request->string('email'))]);

        return response()->json(['message' => 'If an account exists for this email, a reset link has been sent.']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }
}
