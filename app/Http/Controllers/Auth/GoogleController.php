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

        // Find by google_id first, then by email (separate queries to avoid account hijack)
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link google_id to an existing email account (user chose to connect Google)
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId(), 'avatar' => $googleUser->getAvatar()]);
            }
        } else {
            // New user — create with parent role, pending status
            $parentRole = Role::where('name', 'parent')->firstOrFail();

            $user = User::create([
                'role_id'             => $parentRole->id,
                'name'                => $googleUser->getName(),
                'email'               => $googleUser->getEmail(),
                'google_id'           => $googleUser->getId(),
                'avatar'              => $googleUser->getAvatar(),
                'registration_status' => 'pending',
                'is_active'           => true,
            ]);
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
