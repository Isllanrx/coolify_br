<div>
    <x-slot:title>Appearance | Coolify</x-slot>
    <x-theme-controls />
    <section class="application-settings-section mt-6">
        <div class="application-settings-section-header">
            <div>
                <h2>{{ __('profile.language') }}</h2>
                <p>{{ __('profile.language.description') }}</p>
            </div>
        </div>
        <div class="application-settings-section-body max-w-sm">
            <x-forms.listbox id="locale" live searchable :options="$localeOptions" />
        </div>
    </section>
</div>
