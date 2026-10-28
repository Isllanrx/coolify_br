<?php

use App\Http\Kernel;
use App\Http\Middleware\SetLocale;
use App\Livewire\Profile\Appearance;
use App\Models\InstanceSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    InstanceSettings::forceCreate(['id' => 0]);

    Route::middleware(SetLocale::class)->get('/', fn () => app()->getLocale());
});

it('resolves the interface language in the web middleware group', function () {
    expect(app(Kernel::class)->getMiddlewareGroups()['web'])->toContain(SetLocale::class);
});

it('saves the interface language selected in the profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Appearance::class)
        ->assertSet('locale', 'en')
        ->set('locale', 'pt-br')
        ->assertHasNoErrors()
        ->assertRedirect(route('profile.appearance'));

    expect($user->fresh()->locale)->toBe('pt-br');
});

it('rejects interface languages that are not available', function (string $locale) {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Appearance::class)
        ->set('locale', $locale)
        ->assertHasErrors('locale');

    expect($user->fresh()->locale)->toBeNull();
})->with(['xx', 'pt-BR', '../en', '<script>']);

it('applies the language of the authenticated user to web requests', function () {
    $user = User::factory()->create(['locale' => 'pt-br']);

    $this->actingAs($user)->get('/')->assertContent('pt-br');
});

it('keeps the default language for guests and unknown stored values', function () {
    $this->get('/')->assertContent('en');

    $user = User::factory()->create(['locale' => 'xx']);

    $this->actingAs($user)->get('/')->assertContent('en');
});

it('resets the request language when a user has no supported preference', function (?string $locale) {
    $user = User::factory()->create(['locale' => $locale]);
    app()->setLocale('fr');

    $this->actingAs($user)->get('/')->assertContent('en');
})->with([null, 'xx', '../en']);

it('resets the request language for guests after a translated request', function () {
    app()->setLocale('fr');

    $this->get('/')->assertContent('en');
});

it('opens the appearance settings with a supported language for legacy preferences', function () {
    $this->actingAs(User::factory()->create(['locale' => 'xx']));

    Livewire::test(Appearance::class)->assertSet('locale', 'en');
});

it('uses the language of the user for notifications', function () {
    expect(User::factory()->make(['locale' => 'zh-cn'])->preferredLocale())->toBe('zh-cn')
        ->and(User::factory()->make()->preferredLocale())->toBeNull();
});

it('ignores unsupported notification language preferences', function (string $locale) {
    expect(User::factory()->make(['locale' => $locale])->preferredLocale())->toBeNull();
})->with(['xx', '../en']);

it('ships every english translation key for each available language', function () {
    $english = json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach (array_keys(config('app.available_locales')) as $locale) {
        $translations = json_decode(file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

        expect(array_keys(array_diff_key($english, $translations)))->toBe([], "{$locale}.json is missing keys");

        foreach ($english as $key => $text) {
            preg_match_all('/(?<!\w):[a-z_]+/', $text, $expected);
            preg_match_all('/(?<!\w):[a-z_]+/', $translations[$key], $actual);

            expect($actual[0])->toEqualCanonicalizing($expected[0], "{$locale}.json changes the placeholders of {$key}");
        }
    }
});
