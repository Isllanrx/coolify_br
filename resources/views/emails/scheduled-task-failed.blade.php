<x-emails.layout>
{{ __('Scheduled task (:name) was FAILED with the following error:', ['name' => $task->name]) }}

<pre>
{{ $output }}
</pre>

{{ __('Click [here](:url) to view the task.', ['url' => $url]) }}
</x-emails.layout>
