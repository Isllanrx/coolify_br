<div class="h-full">
    <x-slot:title>
        {{ $project->name }} > {{ __('Canvas') }} | Coolify
    </x-slot>
    <div data-environment-canvas data-endpoint="/projects/{{ $project->uuid }}/canvas"
        data-empty="No resources in this project yet"
        data-view-only="Only team admins can move cards and edit links."
        data-team="{{ $project->team_id }}" data-user="{{ auth()->id() }}"
        class="relative h-full" wire:ignore>
        {{-- The canvas moves this group into its toolbar (page actions last, at the right end),
             which can be outside this component,
             so buttons open the modals below with a window event. --}}
        <div data-ec-views class="flex items-center gap-2">
            <x-canvas-view-toggle on-canvas
                :list-url="route('project.show', ['project_uuid' => $project->uuid])"
                :canvas-url="route('project.canvas', ['project_uuid' => $project->uuid])" />
            @can('update', $project)
                <a href="{{ route('project.edit', ['project_uuid' => $project->uuid]) }}" {{ wireNavigate() }}
                    class="button whitespace-nowrap" title="{{ __('Project settings') }}"
                    aria-label="{{ __('Open settings for :name', ['name' => $project->name]) }}">
                    <x-reicon name="settings" class="size-3.5" />
                    {{ __('Settings') }}
                </a>
                <button type="button" x-data x-on:click="$dispatch('open-new-environment')"
                    class="button button-highlighted whitespace-nowrap">
                    <x-reicon name="plus" class="size-3.5" />
                    {{ __('New environment') }}
                </button>
            @endcan
        </div>
    </div>
    @can('update', $project)
        <x-modal-input :title="__('New Environment')" x-on:open-new-environment.window="modalOpen = true">
            <x-slot:content></x-slot:content>
            @include('livewire.project.partials.new-environment-form')
        </x-modal-input>
    @endcan
</div>
