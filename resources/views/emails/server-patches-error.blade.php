<x-emails.layout>
{{ __('Failed to check for package updates on your server :name.', ['name' => $name]) }}

## {{ __('Error Details') }}

- {{ __('Operating System: :value', ['value' => ucfirst($osId)]) }}
- {{ __('Package Manager: :value', ['value' => $package_manager]) }}
- {{ __('Error: :value', ['value' => $error]) }}

---

{{ __('You can manage your server and view more details in your [Coolify Dashboard](:url).', ['url' => $server_url]) }}
</x-emails.layout>
