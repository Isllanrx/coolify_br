@props(['image'])

@php use App\Models\Server; @endphp

<x-callout type="warning" :title="__('Caddy proxy image is outdated')" {{ $attributes }}>
    {!! __('The proxy configuration uses :image. Per-resource traffic analytics and current Caddy features need version 2.9 or newer. To fix this, change the image in the proxy configuration to :recommended, then restart the proxy.', [
        'image' => '<span class="font-mono">'.e($image).'</span>',
        'recommended' => '<span class="font-mono">'.e(Server::RECOMMENDED_CADDY_PROXY_IMAGE).'</span>',
    ]) !!}
</x-callout>
