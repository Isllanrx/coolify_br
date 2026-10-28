<x-emails.layout>

{{ __('Coolify could not send :channel notifications for your team :failures times in a row, so we disabled :channel notifications.', ['channel' => $channel, 'failures' => $failures]) }}

{{ __('Reason: :reason', ['reason' => $reason]) }}

{{ __('Please fix the settings and enable :channel notifications again [here](:url).', ['channel' => $channel, 'url' => $url]) }}
</x-emails.layout>
