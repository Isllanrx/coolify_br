<x-emails.layout>
@if($database_name)
{{ __('Database backup for :name (db::database) with frequency of :frequency succeeded locally but failed to upload to S3.', ['name' => $name, 'database' => $database_name, 'frequency' => $frequency]) }}
@else
{{ __('Database backup for :name with frequency of :frequency succeeded locally but failed to upload to S3.', ['name' => $name, 'frequency' => $frequency]) }}
@endif

{{ __('S3 Error: :error', ['error' => $s3_error]) }}

@if($s3_storage_url)
{{ __('Check S3 Configuration: :url', ['url' => $s3_storage_url]) }}
@endif
</x-emails.layout>
