<x-coach title="Dashboard Coach">
    <x-slot name="navigation">
        <x-sidebar-link href="{{ route('coach.dashboard') }}">Dashboard</x-sidebar-link>
    </x-slot>
    <x-card title="Coach Dashboard">
        <p class="text-slate-500">Welcome, Coach!</p>
    </x-card>
</x-coach>
