<?php

namespace App\Providers;

use App\View\Composers\AdminNavComposer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.admin-nav', AdminNavComposer::class);

        // Inject admin WhatsApp number into every view that needs it
        View::composer('auth.pending', function ($view) {
            $adminRoleId = Role::where('name', 'admin')->value('id');
            $raw = $adminRoleId
                ? User::where('role_id', $adminRoleId)
                    ->whereNotNull('whatsapp_number')
                    ->orderBy('id')
                    ->value('whatsapp_number')
                : null;

            // Normalise to international digits (62xxx) for wa.me link
            $digits = preg_replace('/\D/', '', $raw ?? '');
            if (str_starts_with($digits, '0')) {
                $digits = '62' . substr($digits, 1);
            }

            $view->with('adminWhatsapp', $raw ?? null)
                 ->with('adminWhatsappLink', $digits ? "https://wa.me/{$digits}" : null);
        });
    }
}
