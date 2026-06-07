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
            return redirect()->route('login')->with('error', 'Login Google gagal. Silakan coba lagi.');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId(), 'avatar' => $googleUser->getAvatar()]);
            }
        } else {
            $parentRole = Role::where('name', 'parent')->firstOrFail();

            $user = User::create([
                'role_id'             => $parentRole->id,
                'name'                => $googleUser->getName(),
                'email'               => $googleUser->getEmail(),
                'google_id'           => $googleUser->getId(),
                'avatar'              => $googleUser->getAvatar(),
                'registration_status' => 'pending',
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan.');
        }

        Auth::login($user, remember: true);

        if ($user->registration_status === 'pending') {
            return redirect()->route('pending');
        }

        if ($user->registration_status === 'rejected') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Pendaftaran Anda ditolak.');
        }

        return redirect()->route($user->redirectRouteName());
    }
}
