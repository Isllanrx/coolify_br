<x-emails.layout>
{{ __('Your Coolify API token (:name) expires on :date.', ['name' => $tokenName, 'date' => $expiresAt]) }}

{{ __('Rotate this token before it expires. API calls using this token will start failing once the expiration time is reached.') }}

{{ __('Manage your API tokens [here](:url).', ['url' => $manageUrl]) }}
</x-emails.layout>
