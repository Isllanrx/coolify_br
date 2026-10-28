<x-emails.layout>
{{ $summary }}

{{ __('Frequency: :value', ['value' => $frequency]) }}

@isset($output)
### {{ __('Reason') }}

{{ $output }}
@endisset

@if ($url)
{{ __('Click [here](:url) to open the resource.', ['url' => $url]) }}
@endif
</x-emails.layout>
