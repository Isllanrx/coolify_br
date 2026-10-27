@extends('layouts.base')

@section('body')
    <body class="error-page-body text-black dark:text-inherit">
        <x-toast />
        <x-error-page
            code="503"
            :title="__('error.503_title')"
            :description="__('error.503_desc')" />
    </body>
@endsection
