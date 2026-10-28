@extends('proxy.layout')

@section('title', 'Site not available')

@section('content')
    <p class="status">{{ __('503 Service Unavailable') }}</p>
    <h1>{{ __('This site is not available at the moment') }}</h1>
    <p><span id="host">{{ __('This site') }}</span> {{ __('cannot answer your request right now. Please try again later.') }}</p>
    <section>
        <h2>{{ __('Are you the site owner?') }}</h2>
        <ul>
            <li>{{ __('The domain is not configured on a resource in Coolify, or its DNS points to another server.') }}</li>
            <li>{{ __('The application is stopped, still deploying, or has crashed.') }}</li>
            <li>{{ __('The application health check is failing.') }}</li>
        </ul>
        <a href="https://coolify.io/docs/troubleshoot/applications/no-available-server" rel="noopener noreferrer">{{ __('Read the troubleshooting guide') }}</a>
    </section>
@endsection
