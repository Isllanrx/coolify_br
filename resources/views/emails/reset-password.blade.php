<x-emails.layout>
{{ __('A password reset has been requested for this email address.') }}

{{ __('Click [here](:url) to reset your password.', ['url' => $url]) }}

{{ __('This link will expire in :count minutes.', ['count' => $count]) }}
</x-emails.layout>
