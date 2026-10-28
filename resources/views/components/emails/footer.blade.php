{{ Illuminate\Mail\Markdown::parse('---') }}

{{ __('Thank you,') }}<br>
{{ config('app.name') ?? 'Coolify' }}

{{ Illuminate\Mail\Markdown::parse('['.__('Contact Support').'](https://coolify.io/docs/contact)') }}
