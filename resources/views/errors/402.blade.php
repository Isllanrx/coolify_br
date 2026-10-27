@extends('layouts.base')

@section('body')
    <body class="error-page-body text-black dark:text-inherit">
        <x-toast />
        <x-error-page
            code="402"
            :title="__('error.402_title')"
            :description="__('error.402_desc')" />
    </body>
@endsection
