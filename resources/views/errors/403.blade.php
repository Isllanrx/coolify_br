@extends('layouts.base')

@section('body')
    <body class="error-page-body text-black dark:text-inherit">
        <x-toast />
        <x-error-page
            code="403"
            :title="__('error.403_title')"
            :description="__('error.403_desc')" />
    </body>
@endsection
