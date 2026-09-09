<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google sign-in failed. Please try again.');
        }

        // Find by google_id first (already linked — trusted regardless of verification state).
        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $existing = User::where('email', $googleUser->getEmail())->first();

            if ($existing) {
                // An account with this email exists but was never linked to this Google
                // identity. Only auto-link when that account's email is already verified
                // (self-registration verified by the owner, or pre-verified at import) —
                // otherwise someone could have pre-registered with this email to hijack
                // the real owner's account the moment they sign in with Google.
                if (! $existing->hasVerifiedEmail()) {
                    return redirect()->route('login')->with(
                        'error',
                        'An account with this email already exists but is not verified yet. Please log in with your password, or check your email for a verification link.'
                    );
                }

                $user = $existing;
                $user->update(['google_id' => $googleUser->getId(), 'avatar' => $googleUser->getAvatar()]);
            } else {
                // New user — create with parent role, pending status. Google has already
                // verified this email address for us.
                $parentRole = Role::where('name', 'parent')->firstOrFail();

                $user = User::create([
                    'role_id'             => $parentRole->id,
                    'name'                => $googleUser->getName(),
                    'email'               => $googleUser->getEmail(),
                    'email_verified_at'   => now(),
                    'google_id'           => $googleUser->getId(),
                    'avatar'              => $googleUser->getAvatar(),
                    'registration_status' => 'pending',
                    'is_active'           => true,
                ]);
            }
        }

        if (! $user->is_active) {
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Check rejected BEFORE logging in (avoid creating then destroying session)
        if ($user->registration_status === 'rejected') {
            return redirect()->route('login')->with('error', 'Your registration has been rejected.');
        }

        Auth::login($user, remember: true);

        if ($user->registration_status === 'pending') {
            return redirect()->route('pending');
        }

        return redirect()->route($user->load('role')->redirectRouteName());
    }
}
