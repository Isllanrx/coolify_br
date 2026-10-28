<x-emails.layout>
@foreach ($databases as $database_name => $databases)

@if(data_get($databases,'failed_count') > 0)

<div style="color:red">

{{ __('":name" backups: There were some failed backups. Please login and check the logs for more details.', ['name' => $database_name]) }}

</div>

@else

{{ __('":name" backups: All backups were successful.', ['name' => $database_name]) }}

@endif

@endforeach
</x-emails.layout>
