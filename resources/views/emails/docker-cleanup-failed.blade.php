<x-emails.layout>
{{ __('Docker Cleanup on :name FAILED with the following error:', ['name' => $name]) }}

<pre>
{{ $text }}
</pre>

</x-emails.layout>
