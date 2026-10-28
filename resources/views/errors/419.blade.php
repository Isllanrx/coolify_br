@extends('layouts.base')

@section('body')
    <body class="error-page-body text-black dark:text-inherit">
        <x-toast />
        <x-error-page
            code="419"
            :title="__('error.419_title')"
            :description="__('error.419_desc')"
            :show-go-back="false"
            :show-dashboard="false"
            primary-href="/login"
            :primary-label="__('error.back_to_login')">
            <x-forms.collapsible :title="__('Using a reverse proxy or Cloudflare Tunnel?')" class="error-proxy-help">
                <ul>
                    <li>{!! __('Set your domain in :setting to match the URL you use to access Coolify.', ['setting' => '<strong>'.e(__('Settings')).' &rarr; '.e(__('FQDN')).'</strong>']) !!}</li>
                    <li>{!! __('Cloudflare users: disable :integrity and :attack for your Coolify domain, as these can interrupt login sessions.', ['integrity' => '<strong>'.e(__('Browser Integrity Check')).'</strong>', 'attack' => '<strong>'.e(__('Under Attack Mode')).'</strong>']) !!}</li>
                    <li>{!! __('If you can still access Coolify via :host, log in there first to configure your FQDN.', ['host' => '<code>localhost</code>']) !!}</li>
                </ul>
            </x-forms.collapsible>
        </x-error-page>
        @livewireScripts
    </body>
@endsection
