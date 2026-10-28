<form wire:submit="submit">
    <x-unsaved-bar action="submit" targets="maintenancePage" />
    <x-application.settings-section id="maintenance-section" :title="__('Maintenance mode')"
        :helper="__('Show a maintenance page with a 503 status on all domains of this resource. The containers keep running, and the change applies without a redeploy.')">
        @if ($this->isSupported)
            <div class="flex flex-col gap-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-forms.listbox canGate="update" :canResource="$resource" id="isMaintenanceEnabled"
                        :label="__('Maintenance mode')" onChange="instantSaveMaintenance" :options="[
                            ['value' => false, 'label' => __('Off: serve the resource')],
                            ['value' => true, 'label' => __('On: show the maintenance page')],
                        ]" />
                </div>
                <x-forms.textarea canGate="update" :canResource="$resource" id="maintenancePage" rows="10" monospace
                    :label="__('Custom maintenance page (HTML)')"
                    :placeholder="__('Leave empty to use the Coolify default maintenance page.')"
                    :helper="__('One self-contained HTML file (max :value KB) with inline CSS and images.', ['value' => \App\Models\Server::PROXY_ERROR_PAGE_MAX_BYTES / 1024])" />
                @can('update', $resource)
                    <div class="flex gap-2">
                        <x-forms.button type="button" wire:click="resetMaintenancePage">{{ __('Reset to default') }}</x-forms.button>
                    </div>
                @endcan
            </div>
        @else
            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Maintenance mode needs a Traefik or Caddy proxy on a server that is not part of a Docker Swarm.') }}</p>
        @endif
    </x-application.settings-section>
</form>
