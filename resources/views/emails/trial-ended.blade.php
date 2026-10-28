<x-emails.layout>
{{ __('Your trial ended. All automations and integrations are disabled for all of your servers.') }}

{{ __('Please update payment details [here](:url) or in [Coolify Cloud](https://app.coolify.io) to continue using our services.', ['url' => $stripeCustomerPortal]) }}
</x-emails.layout>
