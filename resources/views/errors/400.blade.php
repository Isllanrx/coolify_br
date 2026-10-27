@extends('layouts.base')

@section('body')
    <body class="error-page-body text-black dark:text-inherit">
        <x-toast />
        <x-error-page
            code="400"
            :title="__('error.400_title')"
            :description="$exception->getMessage() ?: __('error.400_desc')" />
    </body>
@endsection
