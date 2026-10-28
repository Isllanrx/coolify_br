@can('create', App\Models\S3Storage::class)
    <div class="w-full">
        <div class="mb-4">{!! __('For more details, please visit the :link.', ['link' => '<a class="underline dark:text-brand-light" href="https://coolify.io/docs/knowledge-base/s3/introduction" target="_blank">'.e(__('Coolify Docs')).'</a>']) !!}</div>
        <form class="flex flex-col gap-2" wire:submit='submit'>
            <div class="flex gap-2">
                <x-forms.input required :label="__('Name')" id="name" />
                <x-forms.input :label="__('Description')" id="description" />
            </div>
            <x-forms.domain-input id="endpointParts" errorId="endpoint" host-label="Host"
                host-placeholder="minio.internal or 192.168.1.50" />
            <div class="flex gap-2">
                <x-forms.input required :label="__('Bucket')" id="bucket" />
                <x-forms.input required :helper="__('Region only required for AWS. Leave it as-is for other providers.')"
                    :label="__('Region')" id="region" />
            </div>
            <div class="flex gap-2">
                <x-forms.input required type="password" :label="__('Access Key')" id="key" />
                <x-forms.input required type="password" :label="__('Secret Key')" id="secret" />
            </div>

            <x-forms.button class="mt-4" type="submit" wire:target="submit">
                {{ __('Validate Connection & Continue') }}
            </x-forms.button>
        </form>
    </div>
@else
    <x-callout type="danger" :title="__('Insufficient Permissions')">
        {{ __('You don\'t have permission to create new S3 storage configurations. Please contact your team administrator for access.') }}
    </x-callout>
@endcan
