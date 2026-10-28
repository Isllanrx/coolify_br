<div>
    <x-slot:title>
        {{ data_get_str($server, 'name')->limit(10) }} > {{ __('Registries') }} | Coolify
    </x-slot>

    <livewire:server.navbar :server="$server" />

    <div
        class="server-settings-workspace application-settings-workspace mt-4 grid w-full max-w-none min-w-0 gap-8 lg:mt-0 xl:grid-cols-[210px_minmax(0,1fr)] xl:gap-8">
        <x-server.sidebar :server="$server" activeMenu="registries" />

        <div class="application-settings-form flex w-full flex-col gap-3">
            <livewire:server.docker-registries.server-registries :server="$server"
                :key="'server-registries-'.$server->uuid" />
            <p class="text-[12px] text-neutral-500 dark:text-fg-dim">
                {!! __('Registry logins are read from the Docker config that deployments use. Use :login, or run :command on the server.', ['login' => '<b>'.e(__('Log in')).'</b>', 'command' => '<code>docker login &lt;registry&gt;</code>']) !!}
            </p>
        </div>
    </div>
</div>
