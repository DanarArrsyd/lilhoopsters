<?php

namespace App\Livewire;

use App\Http\Middleware\SetLocale;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = app()->getLocale();
    }

    public function switchTo(string $locale)
    {
        if (! in_array($locale, SetLocale::SUPPORTED, true)) {
            return;
        }

        session(['locale' => $locale]);
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        // Full reload so every translated string re-renders under the new locale.
        return $this->redirect(url()->previous() ?: '/', navigate: false);
    }

    public function render()
    {
        return view('livewire.locale-switcher');
    }
}
