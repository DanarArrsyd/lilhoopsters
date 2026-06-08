<x-parent-portal title="Dashboard">
    <x-slot name="navigation">
        <x-sidebar-link href="{{ route('parent.dashboard') }}">Dashboard</x-sidebar-link>
    </x-slot>
    <x-card title="Parent Dashboard">
        <p class="text-slate-500">Welcome to your parent portal!</p>
    </x-card>
</x-parent-portal>
