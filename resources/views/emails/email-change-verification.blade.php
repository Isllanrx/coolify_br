<x-emails.layout>
{{ __('You have requested to change your email address to: :email', ['email' => $newEmail]) }}

{{ __('Please use the following verification code to confirm this change:') }}

{{ __('Verification Code: :code', ['code' => $verificationCode]) }}

{{ __('This code is valid for :minutes minutes.', ['minutes' => $expiryMinutes]) }}

{{ __('If you did not request this change, please ignore this email and your email address will remain unchanged.') }}
</x-emails.layout>