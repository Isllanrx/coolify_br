<div class="flex flex-col items-center justify-center h-32">
    <span class="text-xl font-bold dark:text-white">{{ __('You have reached the limit of') }} {{ $name }} {{ __('you can create.') }}</span>
    <span>{{ __('Please') }} <a class="dark:text-white underline" {{ wireNavigate() }} href="{{ route('subscription.show') }}">{{ __('upgrade your subscription') }}</a> {{ __('to create more') }}
        {{ $name }}.</span>
</div>
