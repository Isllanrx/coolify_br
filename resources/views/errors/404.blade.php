@extends('layouts.base')

@section('body')
    <body class="error-page-body text-black dark:text-inherit">
        <x-toast />
        <x-error-page
            code="404"
            :title="__('error.404_title')"
            :description="__('error.404_desc')" />
    </body>
@endsection
