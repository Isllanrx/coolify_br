<x-emails.layout>
{{ __('We would like to inform you that a :days days of trial has been added to all subscription plans.', ['days' => config('constants.limits.trial_period')]) }}

{{ __('You can try out Coolify, without payment information for free. If you like it, you can upgrade to a paid plan at any time.') }}

{{ __('[Click here](https://app.coolify.io/subscription/new) to start your trial.') }}
</x-emails.layout>
