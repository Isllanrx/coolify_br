<?php

namespace App\Livewire\Profile;

use Illuminate\Validation\Rule;
use Livewire\Component;

class Appearance extends Component
{
    public string $locale;

    public function mount(): void
    {
        $this->locale = auth()->user()->locale ?? config('app.locale');
    }

    public function updatedLocale(): void
    {
        $this->validate([
            'locale' => ['required', Rule::in(array_keys(config('app.available_locales')))],
        ]);

        auth()->user()->update(['locale' => $this->locale]);

        $this->redirectRoute('profile.appearance', navigate: true);
    }

    public function render()
    {
        return view('livewire.profile.appearance', [
            'localeOptions' => collect(config('app.available_locales'))
                ->map(fn (string $label, string $value) => compact('value', 'label'))
                ->values()
                ->all(),
        ]);
    }
}
