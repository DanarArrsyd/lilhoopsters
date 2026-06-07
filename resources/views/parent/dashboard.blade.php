<x-parent-portal title="Dashboard">
    <x-slot name="navigation">
        <x-sidebar-link href="{{ route('parent.dashboard') }}">Dashboard</x-sidebar-link>
    </x-slot>
    <x-card title="Parent Dashboard">
        <p class="text-slate-500">Selamat datang di portal orang tua.</p>
    </x-card>
</x-parent-portal>
