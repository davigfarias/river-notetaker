@props(['findable' => false])

<div {{ $attributes->class('group/tab-group') }} x-data="xTabs(@js(['findable' => (bool) $findable]))">
    {{ $slot }}
</div>
