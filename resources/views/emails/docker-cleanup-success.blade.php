<x-emails.layout>
{{ __('Docker Cleanup on :name succeeded with the following message:', ['name' => $name]) }}


<pre>
{{ $text }}
</pre>

</x-emails.layout>
