<x-emails.layout>
@if($database_name)
{{ __('Database backup for :name (db::database) with frequency of :frequency was successful.', ['name' => $name, 'database' => $database_name, 'frequency' => $frequency]) }}
@else
{{ __('Database backup for :name with frequency of :frequency was successful.', ['name' => $name, 'frequency' => $frequency]) }}
@endif
</x-emails.layout>
