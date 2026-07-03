<div class="max-w-5xl mx-auto">

    <x-admin.page-header title="Backups" subtitle="Database and file backups, retained for 14 days." />

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">Backups run automatically every night at 01:00.</p>
            <button type="button" wire:click="backupNow" wire:loading.attr="disabled"
                    class="bg-navy text-off text-xs font-bold uppercase tracking-wide px-4 py-2 rounded-xl hover:bg-navy/90 transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="backupNow">Backup Now</span>
                <span wire:loading wire:target="backupNow">Running...</span>
            </button>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Filename</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Size</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Created At</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($files as $file)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 text-ink">{{ $file['name'] }}</td>
                            <td class="py-3 px-4 text-muted">{{ number_format($file['size'] / 1024 / 1024, 2) }} MB</td>
                            <td class="py-3 px-4 text-ink">{{ $file['created_at']->format('d M Y H:i') }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('superadmin.backups.download', ['filename' => $file['path']]) }}"
                                   class="text-navy text-xs font-semibold hover:underline">Download</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-2">
                            <x-empty-state title="No backups yet" description="The first automatic backup runs tonight at 01:00, or click Backup Now above." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

</div>
