<x-emails.layout>
{{ $summary }}

@if ($last_execution_at)
{{ __('The last execution was at :time.', ['time' => $last_execution_at]) }}
@else
{{ __('This schedule has never produced an execution.') }}
@endif

@if ($url)
{{ __('Click [here](:url) to open the resource.', ['url' => $url]) }}
@endif
</x-emails.layout>
