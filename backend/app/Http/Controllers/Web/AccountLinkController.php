<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Pages opened from emails (reset password, verify email). They live on the web so
 * they work from any mail client, with or without the app installed.
 */
class AccountLinkController extends Controller
{
    public function showReset(Request $request, string $token): View
    {
        return view('public.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
        $data['email'] = mb_strtolower($data['email']);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete(); // log out every device
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Il link non è valido o è scaduto. Richiedine uno nuovo dall\'app.'])->onlyInput('email');
        }

        return redirect()->route('public.account-done', ['what' => 'password']);
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('public.account-done', ['what' => 'email']);
    }

    public function done(string $what): View
    {
        abort_unless(in_array($what, ['password', 'email'], true), 404);

        return view('public.account-done', ['what' => $what]);
    }
}
