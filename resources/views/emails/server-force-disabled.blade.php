<x-emails.layout>
    {{ __('Your server (:name) disabled because it is not paid! All automations and integrations are stopped.', ['name' => $name]) }}

    {{ __('Please update your subscription to enable the server again [here](https://app.coolify.io/subscription).') }}
</x-emails.layout>
