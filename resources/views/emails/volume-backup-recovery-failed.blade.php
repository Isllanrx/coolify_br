<x-emails.layout>
{{ $summary }}

@if ($url)
{{ __('Click [here](:url) to open the resource.', ['url' => $url]) }}
@endif
</x-emails.layout>
