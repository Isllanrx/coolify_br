<x-emails.layout>
{{ __('Scheduled task (:name) completed successfully with the following output:', ['name' => $task->name]) }}

<pre>
{{ $output }}
</pre>

{{ __('Click [here](:url) to view the task.', ['url' => $url]) }}
</x-emails.layout>
