<?php

namespace App\Livewire\Superadmin;

use App\Models\AuditLog;
use App\Models\Setting;
use Livewire\Component;

class SystemSettings extends Component
{
    public string $academyName    = '';
    public string $academyEmail   = '';
    public string $academyPhone   = '';
    public string $academyAddress = '';
    public string $academyWebsite = '';
    public string $currency       = 'IDR';
    public string $timezone       = 'Asia/Jakarta';

    const KEYS = [
        'academy_name', 'academy_email', 'academy_phone',
        'academy_address', 'academy_website', 'currency', 'timezone',
    ];

    public function mount(): void
    {
        $settings = Setting::allKeyed();

        $this->academyName    = $settings['academy_name']    ?? '';
        $this->academyEmail   = $settings['academy_email']   ?? '';
        $this->academyPhone   = $settings['academy_phone']   ?? '';
        $this->academyAddress = $settings['academy_address'] ?? '';
        $this->academyWebsite = $settings['academy_website'] ?? '';
        $this->currency       = $settings['currency']        ?? 'IDR';
        $this->timezone       = $settings['timezone']        ?? 'Asia/Jakarta';
    }

    public function save(): void
    {
        $this->validate([
            'academyName'  => 'required|string|max:200',
            'academyEmail' => 'nullable|email|max:200',
            'academyPhone' => 'nullable|string|max:30',
            'currency'     => 'required|string|max:10',
            'timezone'     => 'required|string|max:50',
        ]);

        $settings = [
            'academy_name'    => $this->academyName,
            'academy_email'   => $this->academyEmail,
            'academy_phone'   => $this->academyPhone,
            'academy_address' => $this->academyAddress,
            'academy_website' => $this->academyWebsite,
            'currency'        => $this->currency,
            'timezone'        => $this->timezone,
        ];

        Setting::setMany($settings);

        AuditLog::record('system_settings.updated', null, 'Updated system settings', $settings);

        session()->flash('success', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.superadmin.system-settings');
    }
}
