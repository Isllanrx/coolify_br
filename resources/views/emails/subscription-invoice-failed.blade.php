<x-emails.layout>
{{ __('Your last invoice has failed to be paid for Coolify Cloud.') }}

{{ __('Please update payment details [here](:url).', ['url' => $stripeCustomerPortal]) }}
</x-emails.layout>
