<x-mail::message>
# {{ $heading }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@if (!empty($details))
<x-mail::table>
| Detail | |
| :----- | --: |
@foreach ($details as $label => $value)
| **{{ $label }}** | {{ $value }} |
@endforeach
</x-mail::table>
@endif
Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
