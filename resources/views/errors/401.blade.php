@extends('layouts.base')

@section('body')
    <body class="error-page-body text-black dark:text-inherit">
        <x-toast />
        <x-error-page
            code="401"
            :title="__('error.401_title')"
            :description="__('error.401_desc')" />
    </body>
@endsection
