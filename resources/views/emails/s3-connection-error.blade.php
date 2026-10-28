<x-emails.layout>

{{ __('Connection could not be established with one of your S3 Storage (:name). Please fix it [here](:url).', ['name' => $name, 'url' => $url]) }}

{{ $reason }}
</x-emails.layout>
