<x-app>
    <x-slot name="title">{{ $title ?? 'Parent' }}</x-slot>

    <x-portal.content-shell>
        {{ $slot }}
    </x-portal.content-shell>

</x-app>
