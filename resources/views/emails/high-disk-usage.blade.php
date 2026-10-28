<x-emails.layout>
{{ __('Your server (:name) has high disk usage (:usage% used). Threshold is :threshold%.', ['name' => $name, 'usage' => $disk_usage, 'threshold' => $threshold]) }}

{{ __('Please cleanup your disk to prevent data-loss. Here are some [tips](https://coolify.io/docs/knowledge-base/server/automated-cleanup).') }}

{{ __('(You can change the threshold in the Server Settings menu.)') }}
</x-emails.layout>
